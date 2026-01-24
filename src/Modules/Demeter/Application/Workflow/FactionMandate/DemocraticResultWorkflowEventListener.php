<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Event\NewDemocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewLeaderEvent;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Domain\Service\SortCandidatesByVotes;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Event\EnterEvent;

readonly class DemocraticResultWorkflowEventListener
{
	public function __construct(
		private ConversationRepositoryInterface   $conversationRepository,
		private GetFactionsConfiguration          $getFactionsConfiguration,
		private PoliticalEventRepositoryInterface $electionRepository,
		private EventDispatcherInterface          $eventDispatcher,
		private PlayerRepositoryInterface         $playerRepository,
		private SortCandidatesByVotes             $sortCandidatesByVotes,
		private LoggerInterface                   $logger,
		private MessageBusInterface               $messageBus,
		private NextElectionDateCalculator        $nextElectionDateCalculator,
	) {
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Active->value)]
	public function onDemocraticResult(EnterEvent $event): void
	{
		if ($event->getTransition()->getName() !== 'democratic_result') {
			return;
		}

		/** @var Color $faction */
		$faction = $event->getSubject();

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$ballot = ($this->sortCandidatesByVotes)($election);

		$previousLeader = $this->playerRepository->getFactionLeader($faction);

		$this->logger->debug('Ballot for faction {identifier}: {ballot}', [
			'identifier' => $faction->identifier,
			'ballot' => array_map(
				fn (array $data) => sprintf('%s (%d votes)', $data['candidate']->player->name, $data['votes_count']),
				$ballot,
			),
		]);

		$newLeader = current($ballot)['candidate']->player;
		$newLeader->status = Player::CHIEF;
		$this->playerRepository->save($newLeader);

		$this->logger->debug('Democratic election for faction {identifier}: {playerName} won the election', [
			'identifier' => $faction->identifier,
			'playerName' => $newLeader->name,
		]);

		$faction->lastElectionHeldAt = new \DateTimeImmutable();

		$factionPlayer = $this->playerRepository->getFactionAccount($faction);
		$this->eventDispatcher->dispatch(new NewDemocraticLeaderEvent(
			faction: $faction,
			newLeader: $newLeader,
			factionName: ($this->getFactionsConfiguration)($faction, 'popularName'),
			factionStatuses: ($this->getFactionsConfiguration)($faction, 'status'),
			factionPlayer: $factionPlayer,
			factionConversation: $this->conversationRepository->getOneByPlayer($factionPlayer),
			candidatesData: $ballot,
		));

		$this->messageBus->dispatch(
			new CampaignMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getCampaignStartDate($faction))],
		);
	}
}
