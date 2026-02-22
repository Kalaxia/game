<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service;

use App\Modules\Ares\Domain\Model\ShipCategory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Calculates either a Commander or a Planet ships cost.
 */
final readonly class CalculateFleetCost
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		#[Autowire('%game.ship_cost_reduction%')]
		private float $shipCostReduction,
	) {
	}

	/**
	 * @param list<int> $ships
	 *
	 * TODO instead of passing an array of ships, we could implement a ShipStoreInterface on both Commander and Planet entities to retrieve the ships list
	 */
	public function __invoke(array $ships, bool $affected = true): int
	{
		$cost = 0;
		foreach (ShipCategory::cases() as $shipCategory) {
			$cost += ($this->getShipCategoriesConfiguration)($shipCategory, 'cost') * ($ships[$shipCategory->value] ?? 0);
		}
		if (!$affected) {
			$cost *= $this->shipCostReduction;
		}

		return intval(ceil($cost));
	}
}
