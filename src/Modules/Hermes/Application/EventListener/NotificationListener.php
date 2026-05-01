<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Application\EventListener;

use App\Modules\Demeter\Domain\Event\MandateStartEvent;
use App\Modules\Demeter\Domain\Event\NewDemocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewRoyalisticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\PutschFailedEvent;
use App\Modules\Demeter\Domain\Event\UniqueCandidateEvent;
use App\Modules\Hermes\Application\Persister\NotificationPersister;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(PutschFailedEvent::class)]
#[AsEventListener(UniqueCandidateEvent::class)]
#[AsEventListener(MandateStartEvent::class)]
#[AsEventListener(NewDemocraticLeaderEvent::class)]
#[AsEventListener(NewTheocraticLeaderEvent::class)]
#[AsEventListener(NewRoyalisticLeaderEvent::class)]
readonly class NotificationListener
{
	public function __construct(
		private NotificationPersister $notificationPersister,
	) {
	}

	public function __invoke(NotificationEvent $event): void
	{
		foreach ($event->getNotificationBuilders() as $notificationBuilder) {
			$this->notificationPersister->saveFromBuilder($notificationBuilder);
		}
	}
}
