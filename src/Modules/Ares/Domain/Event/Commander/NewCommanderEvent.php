<?php

namespace App\Modules\Ares\Domain\Event\Commander;

use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

readonly class NewCommanderEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public Commander $commander,
		public Player $player,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->player;
	}

	public function getTutorialStep(): ?int
	{
		return TutorialResource::CREATE_COMMANDER;
	}

	public function getTrackingPeopleId(): int
	{
		return $this->commander->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Commander Recruited';
	}

	public function getTrackingData(): array
	{
		return [
			'commander_id' => $this->commander->id,
			'base_id' => $this->commander->base->id,
		];
	}
}
