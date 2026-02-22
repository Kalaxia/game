<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Event\NewTheocraticLeaderEvent;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Shared\Infrastructure\Messenger\ScheduleTask;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Event\EnterEvent;

readonly class TheocraticDesignationWorkflowEventListener
{
	public function __construct(
		private EventDispatcherInterface $eventDispatcher,
		private PoliticalEventRepositoryInterface $electionRepository,
		private CandidateRepositoryInterface $candidateRepository,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private ConversationRepositoryInterface $conversationRepository,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Active->value)]
	public function onTheocraticDesignation(EnterEvent $event): void
	{
		if ('theocratic_designation' !== $event->getTransition()->getName()) {
			return;
		}

		/** @var Color $faction */
		$faction = $event->getSubject();

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$candidates = $this->candidateRepository->getByPoliticalEvent($election);

		$newLeader = $candidates[array_rand($candidates)]->player;

		$factionPlayer = $this->playerRepository->getFactionAccount($faction);

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$ballot = $this->candidateRepository->getByPoliticalEvent($election);

		$this->eventDispatcher->dispatch(new NewTheocraticLeaderEvent(
			faction: $faction,
			newLeader: $newLeader,
			politicalEvent: $election,
			factionName: ($this->getFactionsConfiguration)($faction, 'popularName'),
			factionStatuses: ($this->getFactionsConfiguration)($faction, 'status'),
			factionPlayer: $factionPlayer,
			factionConversation: $this->conversationRepository->getOneByPlayer($factionPlayer),
			candidatesData: $ballot,
		));
	}
}
