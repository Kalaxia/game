<?php

namespace App\Classes\Library;

use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Model\Transaction;

class Game
{
	public const COMMERCIAL_TIME_TRAVEL = 0.2;

	// @TODO Replace by parameters
	public const ANTISPY_DISPLAY_MODE = 0;
	public const ANTISPY_GAME_MODE = 1;

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

	public static function getMaxPriceRelativeToRate($transactionType, $quantity, $identifier = false)
	{
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				$minRate = Transaction::MAX_RATE_RESOURCE;
				break;
			case Transaction::TYP_SHIP:
				$minRate = Transaction::MAX_RATE_SHIP;
				$quantity = ($this->getShipCategoriesConfiguration)($identifier, 'resourcePrice') * $quantity;
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
}
