<?php

namespace App\Modules\Ares\Domain\Event\Fleet;

use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Model\Place;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

readonly class PlannedConquestEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public OrbitalBase $base,
		public Commander $commander,
		public Player    $attacker,
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
			'start_place_faction_identifier' => $this->commander->startPlace->base?->player->faction->identifier,
			'destination_place_id' => $this->commander->destinationPlace,
			'destination_place_player_id' => $this->commander->destinationPlace->base?->player->id,
			'destination_place_faction_identifier' => $this->commander->destinationPlace->base?->player->faction->identifier,
			'commander_id' => $this->commander->id,
		];
	}
}
