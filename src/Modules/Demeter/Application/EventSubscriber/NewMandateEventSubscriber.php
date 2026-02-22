<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\EventSubscriber;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Event\MissingCandidatesEvent;
use App\Modules\Demeter\Domain\Event\NewDemocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\UniqueCandidateEvent;
use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\MandateExpirationMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\DemocraticElection;
use App\Modules\Demeter\Model\Election\DivineDesignation;
use App\Modules\Demeter\Model\Election\Mandate;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Shared\Infrastructure\Messenger\ScheduleTask;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Uid\Uuid;

readonly class NewMandateEventSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private MandateRepositoryInterface $mandateRepository,
		private NextElectionDateCalculator $nextElectionDateCalculator,
		private ScheduleTask $scheduleTask,
	) {
	}

	public function onMissingCandidates(MissingCandidatesEvent $event): void
	{
		$this->onNewMandate(
			$event->factionAccount->faction,
			$event->currentLeader,
			$event->politicalEvent,
		);
	}

	public function onUniqueCandidate(UniqueCandidateEvent $event): void
	{
		$this->onNewMandate(
			$event->newLeader->faction,
			$event->newLeader,
			$event->politicalEvent,
		);
	}

	public function onNewDemocraticLeader(NewDemocraticLeaderEvent $event): void
	{
		$this->onNewMandate(
			$event->newLeader->faction,
			$event->newLeader,
			$event->politicalEvent,
		);
	}

	public function onNewTheocraticLeader(NewTheocraticLeaderEvent $event): void
	{
		$this->onNewMandate(
			$event->newLeader->faction,
			$event->newLeader,
			$event->politicalEvent,
		);
	}

	private function onNewMandate(Color $faction, Player|null $newLeader, PoliticalEvent $politicalEvent): void
	{
		$currentMandate = $this->mandateRepository->getCurrentMandate($faction)
			?? throw new \RuntimeException(sprintf('No mandate found for faction %s.', $faction->identifier));
		$expiredAt = $this->nextElectionDateCalculator->getDurationUntilMandateState(
			$faction,
			MandateState::Active,
			$currentMandate->expiredAt,
		);
		$mandate = new Mandate(
			id: Uuid::v4(),
			faction: $faction,
			leader: $newLeader,
			election: $politicalEvent,
			startedAt: $currentMandate->expiredAt,
			expiredAt: $expiredAt,
		);

		$this->mandateRepository->save($mandate);

		($this->scheduleTask)(
			message: new MandateExpirationMessage($faction->id),
			datetime: $expiredAt,
		);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			MissingCandidatesEvent::class => 'onMissingCandidates',
			NewDemocraticLeaderEvent::class => 'onNewDemocraticLeader',
			NewTheocraticLeaderEvent::class => 'onNewTheocraticLeader',
			UniqueCandidateEvent::class => 'onUniqueCandidates',
		];
	}
}
