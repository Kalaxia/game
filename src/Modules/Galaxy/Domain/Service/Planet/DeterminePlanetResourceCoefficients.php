<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service\Planet;

use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Galaxy\Domain\Enum\PlanetType;

readonly class DeterminePlanetResourceCoefficients
{
	/**
	 * @return array<value-of<ResourceType>, int>
	 */
	public function __invoke(PlanetType $planetType): array
	{
		return array_filter(
			array_map(
				// We apply a random modifier to the natural resources coefficients but keep numbers between 0 and 100
				fn (int $coefficient) => max(0, min($coefficient + random_int(-20, 20), 100)),
				$planetType->getNaturalResourcesBaseCoefficients(),
			),
			// If the obtained coefficient equals 0, we remove it from the planet's natural resources
			fn (int $coefficient) => $coefficient > 0,
		);
	}
}
