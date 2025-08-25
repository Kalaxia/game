<?php

namespace App\Modules\Ares\Domain\Event\Fleet;

use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\TrackingEvent;

readonly class LootEvent implements TrackingEvent
{
	public function __construct(
		public Commander   $commander,
		public Player|null $defender,
	) {
	}

	public function getTrackingPeopleId(): int
	{
		return $this->commander->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Loot';
	}

	public function getTrackingData(): array
	{
		return [
			'commander_id' => $this->commander->id,
			'origin_place_id' => $this->commander->startPlace->id,
			'target_place_id' => $this->commander->destinationPlace->id,
			'is_victorious' => $this->commander->isVictorious(),
			'resources' => $this->commander->resources,
			'defender_id' => $this->defender?->id,
		];
	}
}
