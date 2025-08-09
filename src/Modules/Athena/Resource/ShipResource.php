<?php

namespace App\Modules\Athena\Resource;

use App\Modules\Ares\Domain\Model\ShipCategory;

class ShipResource
{
	public const int SHIP_QUANTITY = 12;

	public const float COST_REDUCTION = 0.8;

	public const array SHIPS = [
		ShipCategory::LightFighter,
		ShipCategory::Fighter,
		ShipCategory::HeavyFighter,
		ShipCategory::LightCorvette,
		ShipCategory::Corvette,
		ShipCategory::HeavyCorvette,
		ShipCategory::LightFrigate,
		ShipCategory::Frigate,
		ShipCategory::Destroyer,
		ShipCategory::HeavyDestroyer,
		ShipCategory::Cruiser,
		ShipCategory::HeavyCruiser,
	];

	/**
	 * pegase = 0, satyre = 1, sirene = 2, dryade = 3, chimere = 4 and meduse = 5.
	 **/
	private static array $dock1Ships = [0, 1, 2, 3, 4, 5];

	/**
	 * griffon = 6, cyclope = 7, minotaure = 8, hydre = 9, cerbere = 10, phenix = 11.
	 **/
	private static array $dock2Ships = [6, 7, 8, 9, 10, 11];

	private static array $femaleShipNames = [2, 3, 4, 5, 9];

	public static function isAShip(int $ship): bool
	{
		return in_array($ship, self::SHIPS);
	}

	public static function isAShipFromDock1(int $ship): bool
	{
		return in_array($ship, self::$dock1Ships);
	}

	public static function isAShipFromDock2(int $ship): bool
	{
		return in_array($ship, self::$dock2Ships);
	}

	public static function isAFemaleShipName(int $ship): bool
	{
		return in_array($ship, self::$femaleShipNames);
	}

	public static function getInfo($shipNumber, $info)
	{
		throw new \LogicException("This method mustn't be used anymore");
	}

	public static function countAvailableShips(): int
	{
		return count(self::$ship);
	}

	private static array $ship = [
	];
}
