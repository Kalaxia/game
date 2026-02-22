<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service;

use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Resource\PlanetResource;

readonly class CountAvailableCommercialShips
{
	public function __construct(
		private CommercialShippingRepositoryInterface $commercialShippingRepository,
		private PlanetHelper $planetHelper,
	) {
	}

	public function __invoke(Planet $base): int
	{
		// verif : have we enough commercialShips
		$totalShips = $this->planetHelper->getBuildingInfo(
			PlanetResource::COMMERCIAL_PLATEFORME,
			'level',
			$base->levelCommercialPlateforme,
			'nbCommercialShip',
		);
		$usedShips = 0;

		// TODO transform this part into an optimized SQL query
		$commercialShippings = $this->commercialShippingRepository->getByPlanet($base);

		foreach ($commercialShippings as $commercialShipping) {
			if ($commercialShipping->originBase->id->equals($base->id)) {
				$usedShips += $commercialShipping->shipQuantity;
			}
		}

		return $totalShips - $usedShips;
	}
}
