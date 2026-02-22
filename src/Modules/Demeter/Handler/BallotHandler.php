<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Handler;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Format;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsGovernmentMember;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class BallotHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private CandidateRepositoryInterface $candidateRepository,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private PlayerRepositoryInterface $playerRepository,
		private MessageBusInterface $messageBus,
		private NotificationRepositoryInterface $notificationRepository,
		private ElectionRepositoryInterface $electionRepository,
		private VoteRepositoryInterface $voteRepository,
		private ConversationRepositoryInterface $conversationRepository,
		private ConversationMessageRepositoryInterface $conversationMessageRepository,
		private NextElectionDateCalculator $nextElectionDateCalculator,
		private UrlGeneratorInterface $urlGenerator,
	) {
	}

	public function __invoke(BallotMessage $message): void
	{
		$faction = $this->colorRepository->get($message->factionId)
			?? throw new \RuntimeException(sprintf('Faction %s not found', $message->factionId));
		if (null === ($election = $this->electionRepository->getFactionLastElection($faction))) {
			return;
		}

		$currentLeader = $this->playerRepository->getFactionLeader($faction);

		$votes = $this->voteRepository->getElectionVotes($election);
		/** @var array<string, array{candidate: Candidate, votes_count: int}> $ballot */
		$ballot = [];

		foreach ($votes as $vote) {
			$candidateId = $vote->candidate->id->toBase32();
			if (!array_key_exists($candidateId, $ballot)) {
				$ballot[$candidateId] = [
					'candidate' => $vote->candidate,
					'votes_count' => 0,
				];
			}
			++$ballot[$candidateId]['votes_count'];
		}

		uasort($ballot, fn ($a, $b) => $b['votes_count'] <=> $a['votes_count']);

		$convPlayer = $this->playerRepository->getFactionAccount($faction);

		$conv = $this->conversationRepository->getOneByPlayer($convPlayer);

		if ($faction->isDemocratic()) {
			if (count($ballot) > 0) {
				arsort($ballot);

				$governmentMembers = $this->playerRepository->getBySpecification(new IsGovernmentMember($faction));

				$newChief = current($ballot)['candidate']->player;

				$this->mandate($faction, $governmentMembers, $newChief, $currentLeader, true, $conv, $convPlayer, $ballot);
			} else {
				$this->mandate($faction, [], null, $currentLeader, false, $conv, $convPlayer, $ballot);
			}
		} elseif ($faction->isRoyalistic()) {
			if (count($ballot) > 0) {
				arsort($ballot);

				if (current($ballot)['candidate']->player->id === $currentLeader?->id) {
					next($ballot);
				}

				// TODO replace by count by IsActiveFactionPlayer specification
				$factionActivePlayers = $this->playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]);

				if (((current($ballot)['votes_count'] / ($factionActivePlayers + 1)) * 100) >= Color::PUTSCHPERCENTAGE) {
					$governmentMembers = $this->playerRepository->getBySpecification(new IsGovernmentMember($faction));
					$newChief = current($ballot)['candidate']->player;
					$this->mandate($faction, $governmentMembers, $newChief, $currentLeader, true, $conv, $convPlayer, $ballot);
				} else {
					$looser = current($ballot)['candidate']->player;
					$this->mandate($faction, [], $looser, $currentLeader, false, $conv, $convPlayer, $ballot);
				}
			}
		} else {
			if (($leader = $this->playerRepository->getFactionLeader($faction)) !== null) {
				if (($candidate = $this->candidateRepository->getByElectionAndPlayer($election, $leader)) !== null) {
					if (0 == random_int(0, 1)) {
						$ballot = [];
					}
				}
			}
			if (count($ballot) > 0) {
				$aleaNbr = random_int(0, count($ballot) - 1);

				for ($i = 0; $i < $aleaNbr; ++$i) {
					next($ballot);
				}

				$governmentMembers = $this->playerRepository->getBySpecification(new IsGovernmentMember($faction));
				$newChief = current($ballot)['candidate']->player;

				$this->mandate($faction, $governmentMembers, $newChief, $currentLeader, true, $conv, $convPlayer, $ballot);
			} else {
				$this->mandate($faction, [], null, $currentLeader, false, $conv, $convPlayer, $ballot);
			}
		}
	}

	/**
	 * @param list<Player>                                                 $governmentMembers
	 * @param ($hadVoted is true ? Player : Player|null)                   $newChief
	 * @param array<string, array{candidate: Candidate, votes_count: int}> $candidates
	 *
	 * @throws \Exception
	 */
	private function mandate(
		Color $color,
		array $governmentMembers,
		?Player $newChief,
		?Player $currentLeader,
		bool $hadVoted,
		Conversation $conv,
		Player $convPlayer,
		array $candidates,
	): void {
		// préparation de la conversation
		$conv->lastMessageAt = new \DateTimeImmutable();
		++$conv->messagesCount;

		// désarchiver tous les users
		$users = $conv->players;
		foreach ($users as $user) {
			$user->conversationStatus = ConversationUser::CS_DISPLAY;
		}
		$mandateDuration = $this->nextElectionDateCalculator->getMandateDuration($color);
		if ($hadVoted) {
			/*			$date = new DateTime($this->dLastElection);
						$date->modify('+' . $this->mandateDuration + self::ELECTIONTIME + self::CAMPAIGNTIME . ' second');
						$date = $date->format('Y-m-d H:i:s');
						$this->dLastElection = $date;*/

			foreach ($governmentMembers as $governmentMember) {
				$governmentMember->status = Player::PARLIAMENT;
			}
			$newChief->status = Player::CHIEF;

			$color->lastElectionHeldAt = new \DateTimeImmutable();
			$color->electionStatement = Color::MANDATE;

			/** @var list<string> $statusArray */
			$statusArray = ($this->getFactionsConfiguration)($color, 'status');
			if ($color->isDemocratic()) {
				$this->messageBus->dispatch(
					new CampaignMessage($color->id),
					[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getCampaignStartDate($color))],
				);
				$notif = NotificationBuilder::new()
					->setTitle('Votre avez été élu')
					->setContent(NotificationBuilder::paragraph(sprintf(
						'Le peuple vous a soutenu, vous avez été élu %s de votre faction.',
						$statusArray[Player::CHIEF - 1],
					)))
					->for($newChief);
				$this->notificationRepository->save($notif);

				$message = new ConversationMessage(
					id: Uuid::v4(),
					conversation: $conv,
					player: $convPlayer,
					content: sprintf(
						'La période électorale est terminée.
						Un nouveau dirigeant a été élu pour faire valoir la force de %s à travers la galaxie.
						Longue vie à <strong>%s</strong>.<br /><br />Voici les résultats des élections :<br /><br />
						%s',
						($this->getFactionsConfiguration)($color, 'popularName'),
						current($candidates)['candidate']->player->name,
						array_map(
							/** @param array{candidate: Candidate, votes_count: int} $player */
							fn (array $player) => sprintf(
								'%s a reçu %d vote%s<br />',
								$player['candidate']->player->name,
								$player['votes_count'],
								Format::plural($player['votes_count']),
							),
							$candidates,
						),
					),
					createdAt: new \DateTimeImmutable(),
				);
				$this->conversationRepository->save($message);
			} elseif ($color->isRoyalistic()) {
				$this->messageBus->dispatch(
					new SenateUpdateMessage($color->id),
					[DateTimeConverter::to_delay_stamp(new \DateTimeImmutable(sprintf('+%d seconds', $mandateDuration)))],
				);
				$this->notificationRepository->save(
					NotificationBuilder::new()
					->setTitle('Votre coup d\'état a réussi')
					->setContent(NotificationBuilder::paragraph(
						'Le peuple vous a soutenu, vous avez renversé le ',
						$statusArray[Player::CHIEF - 1],
						' de votre faction et avez pris sa place.',
					))
					->for($newChief)
				);

				if (null !== $currentLeader) {
					$this->notificationRepository->save(NotificationBuilder::new()
						->setTitle('Un coup d\'état a réussi')
						->setContent(NotificationBuilder::paragraph(
							'Le joueur ',
							NotificationBuilder::link(
								$this->urlGenerator->generate('embassy', ['player' => $newChief->id]),
								$newChief->name,
							),
							' a fait un coup d\'état, vous êtes évincé du pouvoir.',
						))
						->for($currentLeader));
				}

				// création du message
				reset($candidates);
				if (current($candidates)['candidate']->player->id === $currentLeader?->id) {
					next($candidates);
				}
				$message = new ConversationMessage(
					id: Uuid::v4(),
					conversation: $conv,
					player: $convPlayer,
					content: 'Un putsch a réussi, un nouveau dirigeant va faire valoir la force de '.
						($this->getFactionsConfiguration)($color, 'popularName').
						' à travers la galaxie. Longue vie à <strong>'.
						current($candidates)['candidate']->player->name.
						'</strong>.<br /><br />De nombreux membres de la faction ont soutenu le mouvement révolutionnaire :<br /><br />'.
						current($candidates)['candidate']->player->name.
						' a reçu le soutien de '.
						Format::number((current($candidates)['votes_count'] / ($this->playerRepository->countByFactionAndStatements($color, [Player::ACTIVE]) + 1)) * 100).
						'% de la population.<br />',
				);
				$this->conversationMessageRepository->save($message);
			} else {
				$date = \DateTime::createFromImmutable($color->lastElectionHeldAt);
				$date->modify('+'.$mandateDuration.' seconds');
				$this->messageBus->dispatch(
					new CampaignMessage($color->id),
					[DateTimeConverter::to_delay_stamp(\DateTimeImmutable::createFromMutable($date))],
				);

				$this->notificationRepository->save(NotificationBuilder::new()
					->setTitle('Vous avez été nommé Guide')
					->setContent(NotificationBuilder::paragraph(
						'Les Oracles ont parlé, vous êtes désigné par la Grande Lumière pour guider Cardan vers la Gloire.'
					))
					->for($newChief));

				$message = new ConversationMessage(
					id: Uuid::v4(),
					conversation: $conv,
					player: $convPlayer,
					content: 'Les Oracles ont parlé, un nouveau dirigeant va faire valoir la force de '.
						($this->getFactionsConfiguration)($color, 'popularName').
						' à travers la galaxie. Longue vie à <strong>'.
						current($candidates)['candidate']->player->name.
						'</strong>.<br /><br /><br /><br />',
				);
				$this->conversationMessageRepository->save($message);
			}
		} else {
			$noChief = false;
			if (null === $currentLeader) {
				$noChief = true;
				$currentLeader = $this->playerRepository->getByName(($this->getFactionsConfiguration)($color, 'officialName'))
					?? throw new \RuntimeException(sprintf('Missing faction account for %d faction', $color->identifier));
			}
			/*			$date = new DateTime($this->dLastElection);
						$date->modify('+' . $this->mandateDuration + self::ELECTIONTIME + self::CAMPAIGNTIME . ' second');
						$date = $date->format('Y-m-d H:i:s');
						$this->dLastElection = $date;*/
			$color->lastElectionHeldAt = new \DateTimeImmutable();
			$color->electionStatement = Color::MANDATE;

			switch ($color->regime) {
				case Color::REGIME_DEMOCRATIC:
					$date = \DateTime::createFromImmutable($color->lastElectionHeldAt);
					$date->modify('+'.$mandateDuration.' second');
					$this->messageBus->dispatch(
						new CampaignMessage($color->id),
						[DateTimeConverter::to_delay_stamp(\DateTimeImmutable::createFromMutable($date))],
					);

					if (!$noChief) {
						$this->notificationRepository->save(NotificationBuilder::new()
							->setTitle('Vous demeurez '.($this->getFactionsConfiguration)($color, 'status')[Player::CHIEF - 1])
							->setContent(NotificationBuilder::paragraph(
								'Aucun candidat ne s\'est présenté oour vous remplacer lors des dernières élections.',
								'Par conséquent, vous êtes toujours à la tête de ',
								($this->getFactionsConfiguration)($color, 'popularName'),
							))
							->for($currentLeader));
					}
					// création du message
					$message = new ConversationMessage(
						id: Uuid::v4(),
						conversation: $conv,
						player: $convPlayer,
						content: 'La période électorale est terminée. Aucun candidat ne s\'est présenté pour prendre la tête de '.
							($this->getFactionsConfiguration)($color, 'popularName').'.'.
						(false === $noChief)
							? '<br>Par conséquent, '.$currentLeader->name.' est toujours au pouvoir.'
							: '<br>Par conséquent, le siège du pouvoir demeure vacant.',
					);
					$this->conversationMessageRepository->save($message);
					break;
				case Color::REGIME_ROYALISTIC:
					if (null === $newChief) {
						throw new \LogicException('$newChief cannot be null');
					}
					$this->notificationRepository->save(NotificationBuilder::new()
						->setTitle('Votre coup d\'état a échoué')
						->setContent(NotificationBuilder::paragraph(
							'Le peuple ne vous a pas soutenu, l\'ancien gouvernement reste en place.'
						))
						->for($newChief));

					if (!$noChief) {
						$this->notificationRepository->save(NotificationBuilder::new()
							->setTitle('Un coup d\'état a échoué')
							->setContent(NotificationBuilder::paragraph(
								' Le joueur ',
								NotificationBuilder::link(
									$this->urlGenerator->generate('embassy', ['player' => $newChief->id]),
									$newChief->name,
								),
								' a tenté un coup d\'état, celui-ci a échoué.',
							))
							->for($currentLeader));
					}
					$message = new ConversationMessage(
						id: Uuid::v4(),
						conversation: $conv,
						player: $convPlayer,
						content: 'Un coup d\'état a échoué. '.
							$currentLeader->name.
							' demeure le dirigeant de '.
							($this->getFactionsConfiguration)($color, 'popularName'),
					);
					$this->conversationMessageRepository->save($message);
					break;
				case Color::REGIME_THEOCRATIC:
					$date = \DateTime::createFromImmutable($color->lastElectionHeldAt);
					$date->modify('+'.$mandateDuration.' second');
					$this->messageBus->dispatch(
						new CampaignMessage($color->id),
						[DateTimeConverter::to_delay_stamp(\DateTimeImmutable::createFromMutable($date))],
					);

					if (!$noChief) {
						$this->notificationRepository->save(NotificationBuilder::new()
							->setTitle('Vous avez été nommé Guide')
							->setContent(NotificationBuilder::paragraph(
								'Les Oracles ont parlé,',
								' vous êtes toujours désigné par la Grande Lumière pour guider Cardan vers la Gloire.',
							))
							->for($currentLeader));
					}
					$message = new ConversationMessage(
						id: Uuid::v4(),
						conversation: $conv,
						player: $convPlayer,
						content: 'Nul ne s\'est soumis au regard des dieux pour conduire '.
							($this->getFactionsConfiguration)($color, 'popularName').
							' vers sa gloire.'.
							(false === $noChief)
								? $currentLeader->name.' demeure l\'élu des dieux pour accomplir leurs desseins dans la galaxie.'
								: 'Par conséquent, le siège du pouvoir demeure vacant.',
					);
					$this->conversationMessageRepository->save($message);
					break;
			}
		}
	}
}
