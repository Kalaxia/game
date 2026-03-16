<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service\Polygon;

readonly class GetPolygonBounds
{
	/**
	 * Calculate the bbox [minX, minY, maxX, maxY].
	 *
	 * @param list<array{0: float, 1: float}> $vertices
	 *
	 * @return array{0: float, 1: float, 2: float, 3: float}
	 */
	public function __invoke(array $vertices): array
	{
		$minX = $maxX = $vertices[0][0];
		$minY = $maxY = $vertices[0][1];

		foreach ($vertices as [$x, $y]) {
			if ($x < $minX) {
				$minX = $x;
			}
			if ($x > $maxX) {
				$maxX = $x;
			}
			if ($y < $minY) {
				$minY = $y;
			}
			if ($y > $maxY) {
				$maxY = $y;
			}
		}

		return [$minX, $minY, $maxX, $maxY];
	}
}
