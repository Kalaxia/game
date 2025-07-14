<?php

namespace App\Classes\Library;

use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\ShipResource;

class Game
{
	public const COMMERCIAL_TIME_TRAVEL = 0.2;

	// @TODO Replace by parameters
	public const ANTISPY_DISPLAY_MODE = 0;
	public const ANTISPY_LITTLE_CIRCLE = 3;
	public const ANTISPY_MIDDLE_CIRCLE = 2;
	public const ANTISPY_BIG_CIRCLE = 1;
	public const ANTISPY_OUT_OF_CIRCLE = 0;

	public static function convertPlaceType(int $type): string
	{
		return match ($type) {
			1 => 'planète tellurique',
			2 => 'géante gazeuse',
			3 => 'ruine',
			4 => 'poche de gaz',
			5 => 'ceinture d\'astéroïdes',
			6 => 'zone vide',
			default => 'rien',
		};
	}

	public static function getSizeOfPlanet(int|float $population): int
	{
		if ($population < 100) {
			return 1;
		} elseif ($population < 200) {
			return 2;
		} else {
			return 3;
		}
	}

	public static function formatCoord(int $xCoord, int $yCoord, int $planetPosition = 0, int $sectorLocation = 0): string
	{
		if ($sectorLocation > 0) {
			return '⟨'.$sectorLocation.'⟩ '.$xCoord.':'.$yCoord.':'.$planetPosition.'';
		} elseif ($planetPosition > 0) {
			return $xCoord.':'.$yCoord.':'.$planetPosition;
		} else {
			return $xCoord.':'.$yCoord;
		}
	}

	public static function resourceProduction(float $coeffRefinery, int $coeffPlanet): float
	{
		return $coeffRefinery * $coeffPlanet;
	}

	public static function getMaxTravelDistance($bonus): int
	{
		return Commander::DISTANCEMAX;
	}

	public static function calculateCurrentRate($currentRate, $transactionType, $quantity, $identifier, $price)
	{
		// calculate the new rate (when a transaction is accepted)
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				// 1 resource = x credit
				$thisRate = $price / $quantity;
				// dilution of 1%
				$newRate = (($quantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $quantity);

				return max($newRate, Transaction::MIN_RATE_RESOURCE);
				break;
			case Transaction::TYP_SHIP:
				// 1 resource = x credit
				if (ShipResource::isAShip($identifier)) {
					$resourceQuantity = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;
					$thisRate = $price / $resourceQuantity;
					// dilution of 1%
					$newRate = (($resourceQuantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $resourceQuantity);

					return max($newRate, Transaction::MIN_RATE_SHIP);
				} else {
					return false;
				}
				break;
			case Transaction::TYP_COMMANDER:
				// 1 experience = x credit
				$thisRate = $price / $quantity;
				// dilution of 1%
				$newRate = (($quantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $quantity);

				return max($newRate, Transaction::MIN_RATE_COMMANDER);
				break;
			default:
				return 0;
				break;
		}
	}

	public static function calculateRate($transactionType, $quantity, $identifier, $price)
	{
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				// 1 resource = x credit
				return $price / $quantity;
				break;
			case Transaction::TYP_SHIP:
				// 1 resource = x credit
				if (ShipResource::isAShip($identifier)) {
					$resourceQuantity = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;

					return $price / $resourceQuantity;
				} else {
					return false;
				}
				break;
			case Transaction::TYP_COMMANDER:
				// 1 experience = x credit
				return $price / $quantity;
				break;
			default:
				return false;
				break;
		}
	}

	public static function getMinPriceRelativeToRate($transactionType, $quantity, $identifier = null)
	{
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				$minRate = Transaction::MIN_RATE_RESOURCE;
				break;
			case Transaction::TYP_SHIP:
				$minRate = Transaction::MIN_RATE_SHIP;
				$quantity = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;
				break;
			case Transaction::TYP_COMMANDER:
				$minRate = Transaction::MIN_RATE_COMMANDER;
				break;
			default:
				return false;
		}

		$price = round($quantity * $minRate);
		if ($price < 1) {
			$price = 1;
		}

		return $price;
	}

	public static function getMaxPriceRelativeToRate($transactionType, $quantity, $identifier = false)
	{
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				$minRate = Transaction::MAX_RATE_RESOURCE;
				break;
			case Transaction::TYP_SHIP:
				$minRate = Transaction::MAX_RATE_SHIP;
				$quantity = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;
				break;
			case Transaction::TYP_COMMANDER:
				$minRate = Transaction::MAX_RATE_COMMANDER;
				break;
			default:
				return false;
		}

		$price = $quantity * $minRate;

		return round($price);
	}

	public static function getImprovementFromScientificCoef(int $coef): int
	{
		// transform scientific coefficient of a place
		// into improvement coefficient for the technosphere
		if ($coef < 10) {
			return 0;
		} elseif ($coef >= 100) {
			return 40;
		} else {
			return intval(ceil(0.004 * $coef * $coef - 0.01 * $coef + 0.7));
		}
	}

	/**
	 * @param array<int, int> $ships
	 */
	public static function getFleetCost(array $ships, bool $affected = true): int
	{
		$cost = 0;
		for ($i = 0; $i < ShipResource::SHIP_QUANTITY; ++$i) {
			$cost += ShipResource::getInfo($i, 'cost') * ($ships[$i] ?? 0);
		}
		if (!$affected) {
			$cost *= ShipResource::COST_REDUCTION;
		}

		return ceil($cost);
	}
}
