<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Event\NewDemocraticCampaignEvent;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\UpdateSenate;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\DemocraticElection;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\Attribute\AsEnteredListener;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\EnterEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

readonly class DemocraticCampaignWorkflowEventListener
{
	public function __construct(
		private ClockInterface $clock,
		private DurationHandler $durationHandler,
		private EventDispatcherInterface $eventDispatcher,
		private PoliticalEventRepositoryInterface $electionRepository,
		private NextElectionDateCalculator        $nextElectionDateCalculator,
		private UpdateSenate                      $updateSenate,
	) {
	}

	#[AsGuardListener(workflow: 'faction_mandate', transition: 'to_campaign')]
	public function guard(GuardEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		if (!$faction->isDemocratic()) {
			$event->setBlocked(true, sprintf('Faction %s is not democratic', $faction->identifier));
		}
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::DemocraticCampaign->value)]
	public function onCampaignStart(EnterEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		($this->updateSenate)($faction);
	}

	#[AsEnteredListener(workflow: 'faction_mandate', place: MandateState::DemocraticCampaign->value)]
	public function onCampaignStarted(EnteredEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();
		$now = $this->clock->now();
		$campaignEndedAt = $this->durationHandler->getDurationEnd(
			$now,
			$this->nextElectionDateCalculator->getCampaignDuration(),
		);
		$electionEndedAt = $this->durationHandler->getDurationEnd(
			$campaignEndedAt,
			$this->nextElectionDateCalculator->getElectionDuration(),
		);

		$election = new DemocraticElection(
			id: Uuid::v4(),
			faction: $faction,
			startedAt: $now,
			campaignEndedAt: $campaignEndedAt,
			endedAt: $electionEndedAt,
		);

		$this->electionRepository->save($election);

		$this->eventDispatcher->dispatch(new NewDemocraticCampaignEvent($election));
	}
}
