<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service\Polygon;

/**
 *  Propose un espacement minimal en fonction de l'échelle du polygone.
 *  Ici: ~2% de la plus grande dimension de la bbox, borné.
 */
readonly class CalculateMinSpacingBetweenCoordinates
{
	public function __construct(
		private GetPolygonBounds $getPolygonBounds,
	) {
	}

	/**
	 * @param list<array{0: float, 1: float}> $vertices
	 */
	public function __invoke(array $vertices): float
	{
		[$minX, $minY, $maxX, $maxY] = ($this->getPolygonBounds)($vertices);

		$size = max($maxX - $minX, $maxY - $minY);
		$spacing = max(2.0, 0.02 * $size);

		return $spacing;
	}
}
