<?php

namespace App\Modules\Athena\Domain\Event;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

class NewShipQueueEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public readonly ShipQueue $shipQueue,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->shipQueue->base->player;
	}

	public function getTutorialStep(): int|null
	{
		return match (ShipCategory::tryFrom($this->shipQueue->shipNumber)) {
			ShipCategory::LightFighter => TutorialResource::BUILD_SHIP0,
			ShipCategory::Fighter => TutorialResource::BUILD_SHIP1,
			default => null,
		};
	}

	public function getTrackingPeopleId(): int
	{
		return $this->shipQueue->base->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Ships Ordered';
	}

	public function getTrackingData(): array
	{
		return [
			'ship_id' => $this->shipQueue->shipNumber,
			'quantity' => $this->shipQueue->quantity,
			'place_id' => $this->shipQueue->base->id,
		];
	}
}
