<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Event\NewTheocraticLeaderEvent;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Event\EnterEvent;

readonly class TheocraticDesignationWorkflowEventListener
{
	public function __construct(
		private EventDispatcherInterface          $eventDispatcher,
		private PoliticalEventRepositoryInterface $electionRepository,
		private CandidateRepositoryInterface      $candidateRepository,
		private GetFactionsConfiguration          $getFactionsConfiguration,
		private ConversationRepositoryInterface   $conversationRepository,
		private PlayerRepositoryInterface         $playerRepository,
		private MessageBusInterface               $messageBus,
		private NextElectionDateCalculator        $nextElectionDateCalculator,
	) {
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Active->value)]
	public function onTheocraticDesignation(EnterEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		$date = $this->nextElectionDateCalculator->getNextElectionDate($faction);

		$this->messageBus->dispatch(
			new CampaignMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($date)],
		);
		$previousLeader = $this->playerRepository->getFactionLeader($faction);

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$candidates = $this->candidateRepository->getByPoliticalEvent($election);

		$newLeader = $candidates[array_rand($candidates)]->player;

		$faction->lastElectionHeldAt = new \DateTimeImmutable();

		$factionPlayer = $this->playerRepository->getFactionAccount($faction);

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$ballot = $this->candidateRepository->getByPoliticalEvent($election);

		$this->eventDispatcher->dispatch(new NewTheocraticLeaderEvent(
			faction: $faction,
			newLeader: $newLeader,
			factionName: ($this->getFactionsConfiguration)($faction, 'popularName'),
			factionStatuses: ($this->getFactionsConfiguration)($faction, 'status'),
			factionPlayer: $factionPlayer,
			factionConversation: $this->conversationRepository->getOneByPlayer($factionPlayer),
			candidatesData: $ballot,
		));
	}
}
