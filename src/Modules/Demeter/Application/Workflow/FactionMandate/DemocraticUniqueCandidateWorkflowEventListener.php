<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Modules\Demeter\Domain\Event\UniqueCandidateEvent;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Event\EnterEvent;

readonly class DemocraticUniqueCandidateWorkflowEventListener
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
	public function onUniqueCandidate(EnterEvent $event): void
	{
		if ('unique_candidate' !== $event->getTransition()->getName()) {
			return;
		}

		/** @var Color $faction */
		$faction = $event->getSubject();

		$factionAccount = $this->playerRepository->getFactionAccount($faction);
		$factionConversation = $this->conversationRepository->getOneByPlayer($factionAccount);

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$candidates = $this->candidateRepository->getByPoliticalEvent($election);

		if (1 !== count($candidates)) {
			throw new \LogicException('Expected exactly one candidate');
		}

		$newLeader = $candidates[0]->player;
		$this->eventDispatcher->dispatch(new UniqueCandidateEvent(
			factionName: ($this->getFactionsConfiguration)($faction, 'popularName'),
			factionAccount: $factionAccount,
			politicalEvent: $election,
			factionConversation: $factionConversation,
			newLeader: $newLeader,
		));

		$newLeader->status = Player::CHIEF;
		$this->playerRepository->save($newLeader);
	}
}
