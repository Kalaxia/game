<?php

namespace App\Modules\Galaxy\Domain\Event;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;

readonly class PlanetOwnerChangeEvent
{
	public function __construct(
		public Planet $planet,
		public ?Player $previousOwner = null,
	) {
	}
}
