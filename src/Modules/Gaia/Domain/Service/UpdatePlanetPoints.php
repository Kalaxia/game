<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Service;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Modules\Gaia\Resource\PlanetResource;

readonly class UpdatePlanetPoints
{
	public function __construct(
		private BuildingLevelHandler $buildingLevelHandler,
		private PlanetHelper         $planetHelper,
	) {

	}

	public function updatePoints(Planet $planet): int
	{
		$initialPoints = $planet->points;
		$points = 0;

		for ($i = 0; $i < PlanetResource::BUILDING_QUANTITY; ++$i) {
			for ($j = 0; $j < $this->buildingLevelHandler->getBuildingLevel($planet, $i); ++$j) {
				$points += $this->planetHelper->getBuildingInfo($i, 'level', $j + 1, 'resourcePrice') / 1000;
			}
		}

		$points = intval(round($points));
		$planet->points = $points;

		return $points - $initialPoints;
	}
}
