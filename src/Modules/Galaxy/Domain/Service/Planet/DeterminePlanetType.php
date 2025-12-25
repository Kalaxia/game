<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service\Planet;

use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Enum\PlanetType;
use App\Modules\Shared\Application\Service\GetProportion;

readonly class DeterminePlanetType
{
	public function __construct(
		private GetProportion $getProportion,
	) {
	}

	public function __invoke(System $system): PlanetType
	{
		$planetTypes = $system->typeOfSystem->getPlanetTypeProportions();

		return PlanetType::{array_keys($planetTypes)[($this->getProportion)(
			$planetTypes,
			random_int(1, 100),
		) - 1]};
	}
}
