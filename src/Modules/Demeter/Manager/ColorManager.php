<?php

namespace App\Modules\Demeter\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsParliamentMember;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\SchedulerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ColorManager implements SchedulerInterface
{
	public function __construct(
		private ColorRepositoryInterface        $colorRepository,
		private GetFactionsConfiguration 		$getFactionsConfiguration,
		private PlayerRepositoryInterface       $playerRepository,
		private NotificationRepositoryInterface $notificationRepository,
		private MessageBusInterface             $messageBus,
		private UrlGeneratorInterface           $urlGenerator,
		private EntityManagerInterface          $entityManager,
		private NextElectionDateCalculator      $nextElectionDateCalculator,
	) {
	}

	public function schedule(): void
	{
		$this->scheduleSenateUpdate();
		$this->scheduleElections();
		$this->scheduleCampaigns();
		$this->scheduleBallot();
	}

	public function scheduleSenateUpdate(): void
	{
		$factions = $this->colorRepository->getByRegimeAndElectionStatement([Color::REGIME_ROYALISTIC], [Color::MANDATE]);

		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new SenateUpdateMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getSenateUpdateMessage($faction))],
			);
		}
	}

	public function scheduleCampaigns(): void
	{
		$factions = $this->colorRepository->getByRegimeAndElectionStatement(
			[Color::REGIME_DEMOCRATIC, Color::REGIME_THEOCRATIC],
			[Color::MANDATE]
		);

		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new CampaignMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getCampaignStartDate($faction))],
			);
		}
		$factions = $this->colorRepository->getByRegimeAndElectionStatement(
			[Color::REGIME_ROYALISTIC],
			[Color::ELECTION],
		);
		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new BallotMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getPutschEndDate($faction))],
			);
		}
	}

	public function scheduleElections(): void
	{
		$factions = $this->colorRepository->getByRegimeAndElectionStatement(
			[Color::REGIME_DEMOCRATIC],
			[Color::CAMPAIGN],
		);
		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new ElectionMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getNextElectionDate($faction))],
			);
		}
	}

	public function scheduleBallot(): void
	{
		$factions = array_merge(
			$this->colorRepository->getByRegimeAndElectionStatement(
				[Color::REGIME_DEMOCRATIC],
				[Color::ELECTION],
			),
			$this->colorRepository->getByRegimeAndElectionStatement(
				[Color::REGIME_THEOCRATIC],
				[Color::CAMPAIGN, Color::ELECTION],
			)
		);
		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new BallotMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getBallotDate($faction))],
			);
		}
	}

	public function sendSenateNotif(Color $faction, bool $isFromChief = false): void
	{
		$parliamentMembers = $this->playerRepository->getBySpecification(new IsParliamentMember($faction));

		$notificationBuilder = NotificationBuilder::new()
			->setTitle($isFromChief ? 'Loi appliquée' : 'Loi proposée')
			->setContent(NotificationBuilder::paragraph(
				$isFromChief
					? sprintf(
						'Votre %s a appliqué une loi.',
					($this->getFactionsConfiguration)($faction, 'status')[5]
					)
					: 'Votre gouvernement a proposé un projet de loi, en tant que membre du sénat,
					il est de votre devoir de voter pour l\'acceptation ou non de ladite loi.',
				NotificationBuilder::divider(),
				NotificationBuilder::link(
					$this->urlGenerator->generate('faction_senate'),
					$isFromChief ? 'voir les lois appliquées' : 'voir les lois en cours de vote',
				),
			));

		foreach ($parliamentMembers as $parliamentMember) {
			$this->notificationRepository->save($notificationBuilder->for($parliamentMember));
		}
	}

	public function updateSenate(Color $faction): void
	{
		$factionPlayers = $this->playerRepository->getFactionPlayersByRanking($faction);
		$limit = round(count($factionPlayers) / 4);
		// If there is less than 40 players in a faction, the limit is up to 10 senators
		if ($limit < 10) {
			$limit = 10;
		}
		// If there is more than 120 players in a faction, the limit is up to 40 senators
		if ($limit > 40) {
			$limit = 40;
		}

		$senatePromoteNotificationBuilder = NotificationBuilder::new()
			// TODO genders
			->setTitle('Vous êtes sénateur')
			->setContent(NotificationBuilder::paragraph(
				'Vos actions vous ont fait gagner assez de prestige pour faire partie du sénat.',
			));

		$senateDemoteNotificationBuilder = NotificationBuilder::new()
			->setTitle('Vous n\'êtes plus sénateur')
			->setContent(NotificationBuilder::paragraph(
				'Vous n\'avez plus assez de prestige pour rester dans le sénat.'
			));

		foreach ($factionPlayers as $key => $factionPlayer) {
			if ($factionPlayer->isGovernmentMember()) {
				continue;
			}
			if ($key < $limit) {
				if (!$factionPlayer->isParliamentMember()) {
					$this->notificationRepository->save($senatePromoteNotificationBuilder->for($factionPlayer));
				}
				$factionPlayer->status = Player::PARLIAMENT;
			} else {
				if ($factionPlayer->isParliamentMember()) {
					$this->notificationRepository->save($senateDemoteNotificationBuilder->for($factionPlayer));
				}
				// TODO handle ministers
				$factionPlayer->status = Player::STANDARD;
			}
		}
		$this->entityManager->flush();
	}
}
