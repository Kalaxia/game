<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Application\EventListener;

use App\Modules\Demeter\Domain\Event\Government\FiredMinisterEvent;
use App\Modules\Demeter\Domain\Event\Government\NewMinisterEvent;
use App\Modules\Demeter\Domain\Event\MandateStartEvent;
use App\Modules\Demeter\Domain\Event\NewDemocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewRoyalisticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\PutschFailedEvent;
use App\Modules\Demeter\Domain\Event\UniqueCandidateEvent;
use App\Modules\Hermes\Application\Persister\NotificationPersister;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Zeus\Domain\Event\UnmaintainedHangarShipsEvent;
use App\Modules\Zeus\Domain\Event\UnpaidFleetEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(PutschFailedEvent::class)]
#[AsEventListener(UniqueCandidateEvent::class)]
#[AsEventListener(MandateStartEvent::class)]
#[AsEventListener(NewDemocraticLeaderEvent::class)]
#[AsEventListener(NewTheocraticLeaderEvent::class)]
#[AsEventListener(NewRoyalisticLeaderEvent::class)]
#[AsEventListener(UnpaidFleetEvent::class)]
#[AsEventListener(UnmaintainedHangarShipsEvent::class)]
#[AsEventListener(NewMinisterEvent::class)]
#[AsEventListener(FiredMinisterEvent::class)]
final readonly class NotificationListener
{
	public function __construct(
		private NotificationPersister $notificationPersister,
		private UrlGeneratorInterface $urlGenerator,
		private TranslatorInterface $translator,
	) {
	}

	public function __invoke(NotificationEvent $event): void
	{
		foreach ($event->getNotificationBuilders($this->urlGenerator, $this->translator) as $notificationBuilder) {
			$this->notificationPersister->saveFromBuilder($notificationBuilder);
		}
	}
}
