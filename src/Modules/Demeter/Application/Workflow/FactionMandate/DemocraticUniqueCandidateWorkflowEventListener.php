<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Modules\Demeter\Domain\Event\UniqueCandidateEvent;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Event\EnterEvent;

readonly class DemocraticUniqueCandidateWorkflowEventListener
{
	public function __construct(
		private EventDispatcherInterface $eventDispatcher,
		private ElectionRepositoryInterface $electionRepository,
		private CandidateRepositoryInterface $candidateRepository,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private ConversationRepositoryInterface $conversationRepository,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Active->value)]
	public function onUniqueCandidate(EnterEvent $event): void
	{
		if ($event->getTransition()->getName() !== 'unique_candidate') {
			return;
		}

		/** @var Color $faction */
		$faction = $event->getSubject();

		$factionAccount = $this->playerRepository->getFactionAccount($faction);
		$factionConversation = $this->conversationRepository->getOneByPlayer($factionAccount);

		$election = $this->electionRepository->getFactionLastElection($faction);
		$candidates = $this->candidateRepository->getByElection($election);

		if (count($candidates) !== 1) {

		}

		$this->eventDispatcher->dispatch(new UniqueCandidateEvent(
			factionName: ($this->getFactionsConfiguration)($faction, 'popularName'),
			factionAccount: $factionAccount,
			factionConversation: $factionConversation,
			newLeader: $candidates[0]->player,
		));
	}
}
