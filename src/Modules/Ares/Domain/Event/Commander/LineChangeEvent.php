<?php

namespace App\Modules\Ares\Domain\Event\Commander;

use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TutorialEvent;

class LineChangeEvent implements TutorialEvent
{
	public function __construct(
		public readonly Commander $commander,
		public readonly Player $player,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->player;
	}

	public function getTutorialStep(): ?int
	{
		return TutorialResource::MOVE_FLEET_LINE;
	}
}
