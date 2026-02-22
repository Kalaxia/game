<?php

namespace App\Modules\Promethee\Domain\Event;

use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

readonly class NewTechnologyQueueEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public TechnologyQueue $technologyQueue,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->technologyQueue->player;
	}

	public function getTutorialStep(): ?int
	{
		return match ($this->technologyQueue->technology) {
			TechnologyId::SHIP0_UNBLOCK => TutorialResource::SHIP0_UNBLOCK,
			TechnologyId::SHIP1_UNBLOCK => TutorialResource::SHIP1_UNBLOCK,
			default => null,
		};
	}

	public function getTrackingPeopleId(): int
	{
		return $this->technologyQueue->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Technology Search Beginning';
	}

	public function getTrackingData(): array
	{
		return [
			'technology_id' => $this->technologyQueue->technology,
			'place_id' => $this->technologyQueue->place->id,
			'target_level' => $this->technologyQueue->targetLevel,
		];
	}
}
