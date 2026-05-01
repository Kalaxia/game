<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\EventSubscriber;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Event\MissingCandidatesEvent;
use App\Modules\Demeter\Domain\Event\NewDemocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\UniqueCandidateEvent;
use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Message\MandateExpirationMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Mandate;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Shared\Infrastructure\Messenger\ScheduleTask;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Monolog\Attribute\WithMonologChannel;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Uid\Uuid;

#[WithMonologChannel('political_events')]
readonly class NewMandateEventSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private ClockInterface $clock,
		private DurationHandler $durationHandler,
		private MandateRepositoryInterface $mandateRepository,
		private NextElectionDateCalculator $nextElectionDateCalculator,
		private LoggerInterface $logger,
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

	private function onNewMandate(Color $faction, ?Player $newLeader, PoliticalEvent $politicalEvent): void
	{
		$previousMandateExpirationDate = $this->mandateRepository->getCurrentMandate($faction)?->expiredAt
			?? $this->clock->now();
		$expiredAt = $this->durationHandler->getDurationEnd(
			$previousMandateExpirationDate,
			$this->nextElectionDateCalculator->getMandateDuration($faction),
		);

		if ($this->mandateRepository->getMandateByElection($politicalEvent) !== null) {
			$this->logger->info('An existing mandate is already stored for election {electionId}', [
				'electionId' => $politicalEvent->id->toRfc4122(),
			]);

			return;
		}

		$mandate = new Mandate(
			id: Uuid::v4(),
			faction: $faction,
			startedAt: $previousMandateExpirationDate,
			expiredAt: $expiredAt,
			leader: $newLeader,
			election: $politicalEvent,
		);

		$this->mandateRepository->save($mandate);

		($this->scheduleTask)(
			message: new MandateExpirationMessage($mandate->id),
			datetime: $expiredAt,
		);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			MissingCandidatesEvent::class => 'onMissingCandidates',
			NewDemocraticLeaderEvent::class => 'onNewDemocraticLeader',
			NewTheocraticLeaderEvent::class => 'onNewTheocraticLeader',
			UniqueCandidateEvent::class => 'onUniqueCandidate',
		];
	}
}
