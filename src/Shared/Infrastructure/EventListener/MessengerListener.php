<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Modules\Demeter\Domain\Event\NewDemocraticCampaignEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticCampaignEvent;
use App\Shared\Domain\Event\MessengerEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(NewDemocraticCampaignEvent::class)]
#[AsEventListener(NewTheocraticCampaignEvent::class)]
readonly class MessengerListener
{
	public function __construct(
		private MessageBusInterface $messageBus,
	) {
	}

	public function __invoke(MessengerEvent $event): void
	{
		$this->messageBus->dispatch($event->getMessage(), $event->getStamps());
	}
}
