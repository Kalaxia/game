<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service\Polygon;

use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;

readonly class NormalizeSectorVertices
{
	public function __construct(private GalaxyConfiguration $galaxyConfiguration)
	{
	}

	/**
	 * Normalise les vertices en une liste de [float x, float y].
	 * Accepte divers formats: ['x'=>..,'y'=>..], [x, y], "x,y".
	 *
	 * @return list<array{0: float, 1: float}>
	 */
	public function __invoke(Sector $sector): array
	{
		$vertices = array_chunk($this->galaxyConfiguration->getSectorVertices($sector->identifier), 2);

		if (count($vertices) < 3) {
			throw new \InvalidArgumentException(sprintf('Sector %d must have at least 3 vertices to be a valid polygon', $sector->identifier));
		}

		return $vertices;
	}
}
