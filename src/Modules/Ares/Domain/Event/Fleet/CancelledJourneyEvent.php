<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Event\Fleet;

use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Model\Commander;
use App\Shared\Domain\Event\TrackingEvent;

readonly class CancelledJourneyEvent implements TrackingEvent
{
	public function __construct(
		public Commander $commander,
		public CommanderMission $mission,
	) {
	}


	public function getTrackingPeopleId(): int
	{
		return $this->commander->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Cancelled Journey';
	}

	public function getTrackingData(): array
	{
		return [
			'commander_id' => $this->commander->id,
			'mission_type' => $this->mission->value,
			'start_place_id' => $this->commander->startPlace->id,
			'destination_place_id' => $this->commander->destinationPlace->id,
			'start_place_faction_identifier' => $this->commander->startPlace->base?->player->faction->identifier,
			'destination_place_faction_identifier' => $this->commander->destinationPlace->base?->player->faction->identifier,
		];
	}
}
