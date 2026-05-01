<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service\Polygon;

/**
 * Test point-dans-polygone (ray casting).
 */
readonly class IsPointInPolygon
{
	/**
	 * @param list<array{0: float, 1: float}> $vertices
	 */
	public function __invoke(float $x, float $y, array $vertices): bool
	{
		$isInside = false;
		$n = count($vertices);

		for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
			[$xi, $yi] = $vertices[$i];
			[$xj, $yj] = $vertices[$j];

			$doesIntersect = (($yi > $y) !== ($yj > $y))
				&& ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

			if ($doesIntersect) {
				$isInside = !$isInside;
			}
		}

		return $isInside;
	}
}
