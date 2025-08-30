<?php

namespace App\Modules\Ares\Domain\Event\Fleet;

use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

class PlannedLootEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public readonly Place $place,
		public readonly Commander $commander,
		public readonly Player $attacker,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->attacker;
	}

	public function getTutorialStep(): int|null
	{
		return TutorialResource::LOOT_PLANET;
	}

	public function getTrackingPeopleId(): int
	{
		return $this->attacker->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Planned Loot Mission';
	}

	public function getTrackingData(): array
	{
		return [
			'start_place_id' => $this->commander->startPlace,
			'destination_place_id' => $this->commander->destinationPlace,
			'commander_id' => $this->commander->id,
		];
	}
}
