<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsFromFaction;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class MakeACoup extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NextElectionDateCalculator $nextElectionDateCalculator,
		NotificationRepositoryInterface $notificationRepository,
		PlayerManager $playerManager,
		PlayerRepositoryInterface $playerRepository,
		CandidateRepositoryInterface $candidateRepository,
		VoteRepositoryInterface $voteRepository,
		ElectionRepositoryInterface $electionRepository,
		ForumTopicRepositoryInterface $forumTopicRepository,
		MessageBusInterface $messageBus,
	): Response {
		$program = $request->request->get('program') ?? throw new BadRequestHttpException('Missing program');
		$chiefChoice = $request->request->get('chiefchoice');
		$treasurerChoice = $request->request->get('treasurerchoice');
		$warlordChoice = $request->request->get('warlordchoice');
		$ministerChoice = $request->request->get('ministerchoice');

		// TODO Replace with voter
		if (!$currentPlayer->isParliamentMember() || $currentPlayer->isRuler()) {
			throw $this->createAccessDeniedException('Vous ne pouvez pas vous présenter, vous ne faite pas partie de l\'élite ou vous êtes déjà le hef de la faction.');
		}
		$faction = $currentPlayer->faction;

		if (Color::MANDATE !== $faction->electionStatement) {
			throw new ConflictHttpException('Un coup d\'état est déjà en cours.');
		}
		// TODO allow coups for democratic factions
		if (!$faction->isRoyalistic()) {
			throw new ConflictHttpException('Vous vivez dans une faction démocratique.');
		}
		$election = new Election(
			id: Uuid::v4(),
			faction: $faction,
			dElection: new \DateTimeImmutable(),
		);

		$electionRepository->save($election);

		$candidate = new Candidate(
			id: Uuid::v4(),
			election: $election,
			player: $currentPlayer,
			chiefChoice: $chiefChoice,
			treasurerChoice: $treasurerChoice,
			warlordChoice: $warlordChoice,
			ministerChoice: $ministerChoice,
			program: $program,
			createdAt: new \DateTimeImmutable(),
		);
		$candidateRepository->save($candidate);

		$topic = new ForumTopic(
			id: Uuid::v4(),
			// TODO genders
			title: sprintf('Candidat %s', $currentPlayer->name),
			player: $currentPlayer,
			// TODO transform into constant
			forum: 30,
			faction: $currentPlayer->faction,
		);
		$forumTopicRepository->save($topic);

		$faction->electionStatement = Color::ELECTION;
		$faction->lastElectionHeldAt = new \DateTimeImmutable();

		$vote = new Vote(
			id: Uuid::v4(),
			candidate: $candidate,
			player: $currentPlayer,
			hasApproved: true,
			votedAt: new \DateTimeImmutable(),
		);
		$voteRepository->save($vote);

		$factionPlayers = $playerRepository->getBySpecification(new IsFromFaction($faction));

		$notificationBuilder = NotificationBuilder::new()
			->setTitle('Coup d\'Etat.')
			->setContent(NotificationBuilder::paragraph(
				'Un membre de votre Faction soulève une partie du peuple et tente un coup d\'état contre le gouvernement.',
				NotificationBuilder::divider(),
				NotificationBuilder::link(
					$this->generateUrl('view_faction_election'),
					'prendre parti sur le coup d\'état.',
				),
			));

		foreach ($factionPlayers as $factionPlayer) {
			if (Player::ACTIVE !== $factionPlayer->statement) {
				continue;
			}
			$notificationRepository->save($notificationBuilder->for($factionPlayer));
		}
		$this->addFlash('success', 'Coup d\'état lancé.');

		$messageBus->dispatch(
			new BallotMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($nextElectionDateCalculator->getPutschEndDate($faction))],
		);

		return $this->redirect($request->headers->get('referer'));
	}
}
