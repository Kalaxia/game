<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service\Planet;

use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Enum\PlanetType;

readonly class DeterminePopulation
{
	public function __invoke(System $system, PlanetType $planetType): int
	{
		if ($system->sector->faction === null) {
			return 0;
		}

		if (random_int(0, 100) < 50) {
			return 0;
		}

		[$min, $max] = $planetType->getPopulationBaseRange();

		return random_int($min, $max);
	}
}
