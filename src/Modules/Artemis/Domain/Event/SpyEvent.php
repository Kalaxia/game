<?php

namespace App\Modules\Artemis\Domain\Event;

use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

readonly class SpyEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public SpyReport $spyReport,
		public Player $player,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->player;
	}

	public function getTutorialStep(): ?int
	{
		return TutorialResource::SPY_PLANET;
	}

	public function getTrackingPeopleId(): int
	{
		return $this->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'New Spy Report';
	}

	public function getTrackingData(): array
	{
		return [
			'place' => $this->spyReport->place,
			'enemy' => $this->spyReport->targetPlayer,
			'price' => $this->spyReport->price,
			'success_rate' => $this->spyReport->successRate,
		];
	}
}
