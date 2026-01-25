<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service;

use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Service\Polygon\CalculateMinSpacingBetweenCoordinates;
use App\Modules\Galaxy\Domain\Service\Polygon\GetPolygonBounds;
use App\Modules\Galaxy\Domain\Service\Polygon\IsPointInPolygon;
use App\Modules\Galaxy\Domain\Service\Polygon\NormalizeSectorVertices;

class CreateMultipleSystemCoordinates
{
	public function __construct(
		private CalculateMinSpacingBetweenCoordinates $calculateMinSpacingBetweenCoordinates,
		private IsPointInPolygon $isPointInPolygon,
		private GetPolygonBounds $getPolygonBounds,
		private NormalizeSectorVertices $normalizeSectorVertices,
	) {
	}

	/**
	 * @return list<array{0: int, 1: int}>
	 */
	public function __invoke(
		Sector $sector,
		int $maxAttempts,
		int $targetCount,
	): array {
		$vertices = ($this->normalizeSectorVertices)($sector);
		[$minX, $minY, $maxX, $maxY] = ($this->getPolygonBounds)($vertices);
		$minSpacing = pow(($this->calculateMinSpacingBetweenCoordinates)($vertices), 2);

		$points = [];
		$attempts = 0;
		while (count($points) < $targetCount && $attempts < $maxAttempts) {
			++$attempts;

			$x = $this->randFloat($minX, $maxX);
			$y = $this->randFloat($minY, $maxY);

			if (!($this->isPointInPolygon)($x, $y, $vertices)) {
				continue;
			}

			// Test d'espacement minimal
			$ok = true;
			foreach ($points as [$px, $py]) {
				if ($this->dist2($x, $y, $px, $py) < $minSpacing) {
					$ok = false;
					break;
				}
			}

			if ($ok) {
				$points[] = [
					intval(round($x)),
					intval(round($y)),
				];
			}
		}

		return $points;
	}

	private function randFloat(float $min, float $max): float
	{
		return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
	}

	private function dist2(float $x1, float $y1, float $x2, float $y2): float
	{
		$dx = $x1 - $x2;
		$dy = $y1 - $y2;

		return $dx * $dx + $dy * $dy;
	}
}
