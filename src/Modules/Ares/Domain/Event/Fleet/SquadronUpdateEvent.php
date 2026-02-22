<?php

namespace App\Modules\Ares\Domain\Event\Fleet;

use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TutorialEvent;

class SquadronUpdateEvent implements TutorialEvent
{
	public function __construct(
		public readonly Commander $commander,
		public readonly Squadron $squadron,
		public readonly Player $player,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->player;
	}

	public function getTutorialStep(): ?int
	{
		return TutorialResource::FILL_SQUADRON;
	}
}
