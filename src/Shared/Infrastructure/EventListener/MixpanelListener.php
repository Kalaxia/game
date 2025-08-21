<?php

namespace App\Shared\Infrastructure\EventListener;

use App\Modules\Ares\Domain\Event\Commander\NewCommanderEvent;
use App\Modules\Ares\Domain\Event\Commander\SchoolAssignmentEvent;
use App\Modules\Ares\Domain\Event\Fleet\CancelledJourneyEvent;
use App\Modules\Ares\Domain\Event\Fleet\LootEvent;
use App\Modules\Ares\Domain\Event\Fleet\PlannedLootEvent;
use App\Modules\Artemis\Domain\Event\SpyEvent;
use App\Modules\Athena\Domain\Event\NewBuildingQueueEvent;
use App\Modules\Athena\Domain\Event\NewShipQueueEvent;
use App\Modules\Promethee\Domain\Event\NewTechnologyQueueEvent;
use App\Modules\Zeus\Domain\Event\PlayerConnectionEvent;
use App\Shared\Domain\Event\TrackingEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class MixpanelListener
{
	public function __construct(
		private \Mixpanel $mixpanel,
		#[Autowire('%kernel.environment%')]
		string $environment,
	) {
		$this->mixpanel->register('environment', $environment);
	}

	#[AsEventListener(priority: 10)]
	public function onPlayerConnection(PlayerConnectionEvent $event): void
	{
		$player = $event->player;

		$this->mixpanel->people->set($player->id, [
			'faction_id' => $player->faction->id,
		]);
	}

	#[AsEventListener(NewBuildingQueueEvent::class)]
	#[AsEventListener(NewShipQueueEvent::class)]
	#[AsEventListener(NewTechnologyQueueEvent::class)]
	#[AsEventListener(NewCommanderEvent::class)]
	#[AsEventListener(SchoolAssignmentEvent::class)]
	#[AsEventListener(PlannedLootEvent::class)]
	#[AsEventListener(LootEvent::class)]
	#[AsEventListener(CancelledJourneyEvent::class)]
	#[AsEventListener(PlayerConnectionEvent::class)]
	#[AsEventListener(SpyEvent::class)]
	public function onTrackingEvent(TrackingEvent $event): void
	{
		$this->mixpanel->identify($event->getTrackingPeopleId());

		$this->mixpanel->track($event->getTrackingEventName(), $event->getTrackingData());
	}
}
