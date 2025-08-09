<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Recycling;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Shared\Application\PercentageApplier;

class RecycleShips
{
	public function __invoke(
		GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		RecyclingMission $recyclingMission,
		int $extractionPoints,
	): array {
		$shipRecycled = PercentageApplier::toInt($extractionPoints, $recyclingMission->target->coefHistory);

		// convert shipRecycled to real ships
		$pointsToRecycle = round($shipRecycled * RecyclingMission::COEF_SHIP);
		$shipsArray1 = [];
		$buyShip = [];
		foreach (ShipCategory::cases() as $shipCategory) {
			if (floor($pointsToRecycle / $getShipCategoriesConfiguration($shipCategory, 'resourcePrice')) > 0) {
				$shipsArray1[] = [
					'ship' => $shipCategory->value,
					'price' => $getShipCategoriesConfiguration($shipCategory, 'resourcePrice'),
					'canBuild' => true,
				];
			}
			$buyShip[] = 0;
		}

		shuffle($shipsArray1);
		$shipsArray = [];
		$onlyThree = 0;
		foreach ($shipsArray1 as $key => $value) {
			++$onlyThree;
			$shipsArray[] = $value;
			if (3 == $onlyThree) {
				break;
			}
		}
		$continue = true;
		if (count($shipsArray) > 0) {
			while ($continue) {
				foreach ($shipsArray as $key => $line) {
					if ($line['canBuild']) {
						$nbmax = intval(floor($pointsToRecycle / $line['price']));
						if ($nbmax < 1) {
							$shipsArray[$key]['canBuild'] = false;
						} else {
							$qty = random_int(1, $nbmax);
							$pointsToRecycle -= $qty * $line['price'];
							$buyShip[$line['ship']] += $qty;
						}
					}
				}

				$canBuild = false;
				// verify if we can build one more ship
				foreach ($shipsArray as $line) {
					if ($line['canBuild']) {
						$canBuild = true;
						break;
					}
				}
				if (!$canBuild) {
					// if the 3 types of ships can't be build anymore --> stop
					$continue = false;
				}
			}
		}

		return $buyShip;
	}
}
