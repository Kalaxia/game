<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Athena\Resource\ShipResource;

/**
 * Calculates either a Commander or an OrbitalBase ships cost
 */
final readonly class CalculateFleetCost
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	/**
	 * @param list<int> $ships
	 *
	 * TODO instead of passing an array of ships, we could implement a ShipStoreInterface on both Commander and OrbitalBase entities to retrieve the ships list
	 */
	public function __invoke(array $ships, bool $affected = true): int
	{
		$cost = 0;
		foreach (ShipCategory::cases() as $shipCategory) {
			$cost += ($this->getShipCategoriesConfiguration)($shipCategory, 'cost') * ($ships[$shipCategory->value] ?? 0);
		}
		if (!$affected) {
			$cost *= ShipResource::COST_REDUCTION;
		}

		return intval(ceil($cost));
	}
}
