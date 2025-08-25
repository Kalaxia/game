<?php

namespace App\Modules\Ares\Domain\Event\Commander;

use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TutorialEvent;

readonly class AffectationEvent implements TutorialEvent
{
	public function __construct(
		public Commander $commander,
		public Player    $player,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->player;
	}

	public function getTutorialStep(): int|null
	{
		return TutorialResource::AFFECT_COMMANDER;
	}
}
