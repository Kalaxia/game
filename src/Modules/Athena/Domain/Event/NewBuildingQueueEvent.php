<?php

namespace App\Modules\Athena\Domain\Event;

use App\Modules\Athena\Model\BuildingQueue;
use App\Shared\Domain\Event\TrackingEvent;

class NewBuildingQueueEvent implements TrackingEvent
{
	public function __construct(
		public readonly BuildingQueue $buildingQueue,
	) {
	}

	public function getTrackingPeopleId(): int
	{
		return $this->buildingQueue->base->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Building Planned';
	}

	public function getTrackingData(): array
	{
		return [
			'building_id' => $this->buildingQueue->buildingNumber,
			'target_level' => $this->buildingQueue->targetLevel,
			'base_id' => $this->buildingQueue->base->id,
		];
	}
}
