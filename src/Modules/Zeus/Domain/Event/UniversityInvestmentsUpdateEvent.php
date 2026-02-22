<?php

namespace App\Modules\Zeus\Domain\Event;

use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TutorialEvent;

class UniversityInvestmentsUpdateEvent implements TutorialEvent
{
	public function __construct(
		public readonly Player $player,
		public readonly int $amount,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->player;
	}

	public function getTutorialStep(): ?int
	{
		return TutorialResource::MODIFY_UNI_INVEST;
	}
}
