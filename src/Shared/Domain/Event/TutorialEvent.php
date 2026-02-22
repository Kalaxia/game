<?php

namespace App\Shared\Domain\Event;

use App\Modules\Zeus\Model\Player;

interface TutorialEvent
{
	public function getTutorialPlayer(): Player;

	public function getTutorialStep(): ?int;
}
