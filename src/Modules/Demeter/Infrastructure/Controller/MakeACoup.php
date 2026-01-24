<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Model\Election\Putsch;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsFromFaction;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

class MakeACoup extends AbstractController
{
	public function __invoke(
		Request                           $request,
		ClockInterface                    $clock,
		Player                            $currentPlayer,
		DurationHandler                   $durationHandler,
		NextElectionDateCalculator        $nextElectionDateCalculator,
		NotificationRepositoryInterface   $notificationRepository,
		PlayerManager                     $playerManager,
		PlayerRepositoryInterface         $playerRepository,
		CandidateRepositoryInterface      $candidateRepository,
		VoteRepositoryInterface           $voteRepository,
		PoliticalEventRepositoryInterface $politicalEventRepository,
		ForumTopicRepositoryInterface     $forumTopicRepository,
		MessageBusInterface               $messageBus,
		WorkflowInterface                 $factionMandateWorkflow,
	): Response {

		// TODO Replace with voter
		if (!$currentPlayer->isParliamentMember() || $currentPlayer->isRuler()) {
			throw $this->createAccessDeniedException('Vous ne pouvez pas vous présenter, vous ne faite pas partie de l\'élite ou vous êtes déjà le hef de la faction.');
		}
		$faction = $currentPlayer->faction;

		$factionMandateWorkflow->apply($faction, 'royalistic_putsch');

		$startedAt = $clock->now();

		$putsch = new Putsch(
			id: Uuid::v4(),
			faction: $faction,
			startedAt: $startedAt,
			endedAt: $durationHandler->getDurationEnd($startedAt, $nextElectionDateCalculator->getPutschDuration()),
		);

		$politicalEventRepository->save($putsch);

		$program = $request->request->get('program')
			?? throw new BadRequestHttpException('Missing program');
		$chiefChoice = $request->request->get('chiefchoice');
		$treasurerChoice = $request->request->get('treasurerchoice');
		$warlordChoice = $request->request->get('warlordchoice');
		$ministerChoice = $request->request->get('ministerchoice');

		$candidate = new Candidate(
			id: Uuid::v4(),
			politicalEvent: $putsch,
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

		$faction->lastElectionHeldAt = new \DateTimeImmutable();

		$vote = new Vote(
			id: Uuid::v4(),
			candidate: $candidate,
			player: $currentPlayer,
			hasApproved: true,
			votedAt: new \DateTimeImmutable(),
		);
		$voteRepository->save($vote);

		$this->addFlash('success', 'Coup d\'état lancé.');

		return $this->redirect($request->headers->get('referer'));
	}
}
