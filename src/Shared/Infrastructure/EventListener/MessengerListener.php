<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Modules\Demeter\Domain\Event\NewDemocraticCampaignEvent;
use App\Modules\Demeter\Domain\Event\NewPutschAttemptEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticCampaignEvent;
use App\Modules\Shared\Infrastructure\Messenger\ScheduleTask;
use App\Shared\Domain\Event\MessengerEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(NewDemocraticCampaignEvent::class)]
#[AsEventListener(NewTheocraticCampaignEvent::class)]
#[AsEventListener(NewPutschAttemptEvent::class)]
readonly class MessengerListener
{
	public function __construct(
		private ScheduleTask $scheduleTask,
	) {
	}

	public function __invoke(MessengerEvent $event): void
	{
		($this->scheduleTask)($event->getMessage(), $event->getDelay());
	}
}
