<?php

namespace App\Modules\Gaia\Domain\Event;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;

readonly class PlanetOwnerChangeEvent
{
	public function __construct(
		private Planet $planet,
		private Player $previousOwner,
	) {

	}

	public function getPlanet(): Planet
	{
		return $this->planet;
	}

	public function getPreviousOwner(): Player
	{
		return $this->previousOwner;
	}
}
