<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Handler;

use App\Modules\Gaia\Domain\Entity\Place;

readonly class GetDistanceBetweenPlaces
{
	public function __invoke(Place $from, Place $to): int
	{
		return $this->getDistance(
			$from->system->xPosition,
			$to->system->xPosition,
			$from->system->yPosition,
			$to->system->yPosition,
		);
	}

	private function getDistance(int $xa, int $xb, int $ya, int $yb): int
	{
		$distance = intval(floor(sqrt(($xa - $xb) ** 2 + ($ya - $yb) ** 2)));

		return max(1, $distance);
	}
}
