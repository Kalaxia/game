<?php

namespace App\Modules\Promethee\Model;

class TechnologyId
{
	public const COM_PLAT_UNBLOCK = 0;
	public const DOCK2_UNBLOCK = 1;
	public const DOCK3_UNBLOCK = 2;			// inactif
	public const RECYCLING_UNBLOCK = 3;
	public const SPATIOPORT_UNBLOCK = 4;
	public const SHIP0_UNBLOCK = 5;	// light_fighter
	public const SHIP1_UNBLOCK = 6;	// fighter
	public const SHIP2_UNBLOCK = 7;	// heavy_fighter
	public const SHIP3_UNBLOCK = 8;	// light_corvette
	public const SHIP4_UNBLOCK = 9;	// corvette
	public const SHIP5_UNBLOCK = 10;	// heavy_corvette
	public const SHIP6_UNBLOCK = 11;	// light_frigate
	public const SHIP7_UNBLOCK = 12;	// frigate
	public const SHIP8_UNBLOCK = 13;	// destroyer
	public const SHIP9_UNBLOCK = 14;	// heavy_destroyer
	public const SHIP10_UNBLOCK = 15;	// cruiser
	public const SHIP11_UNBLOCK = 16;	// heavy_cruiser
	public const COLONIZATION = 17;
	public const CONQUEST = 18;

	public const GENERATOR_SPEED = 19;			// ok
	public const REFINERY_REFINING = 20;		// ok
	public const REFINERY_STORAGE = 21;		// ok
	public const DOCK1_SPEED = 22;				// ok
	public const DOCK2_SPEED = 23;				// ok
	public const TECHNOSPHERE_SPEED = 24;		// ok
	public const COMMERCIAL_INCOME = 25;		// ok
	public const GRAVIT_MODULE = 26;			// inactif
	public const DOCK3_SPEED = 27;				// inactif
	public const POPULATION_TAX = 28;			// ok
	public const COMMANDER_INVEST = 29;		// ok
	public const UNI_INVEST = 30;				// ok
	public const ANTISPY_INVEST = 31;
	public const SPACESHIPS_SPEED = 32;
	public const SPACESHIPS_CONTAINER = 33;
	public const BASE_QUANTITY = 34;
	public const FIGHTER_SPEED = 35;
	public const FIGHTER_ATTACK = 36;
	public const FIGHTER_DEFENSE = 37;
	public const CORVETTE_SPEED = 38;
	public const CORVETTE_ATTACK = 39;
	public const CORVETTE_DEFENSE = 40;
	public const FRIGATE_SPEED = 41;
	public const FRIGATE_ATTACK = 42;
	public const FRIGATE_DEFENSE = 43;
	public const DESTROYER_SPEED = 44;
	public const DESTROYER_ATTACK = 45;
	public const DESTROYER_DEFENSE = 46;

	public const UNBLOCKING_TECHNOLOGIES_IDS = [
		self::COM_PLAT_UNBLOCK,
		self::DOCK2_UNBLOCK,
		self::DOCK3_UNBLOCK,
		self::RECYCLING_UNBLOCK,
		self::SPATIOPORT_UNBLOCK,
		self::SHIP0_UNBLOCK,
		self::SHIP1_UNBLOCK,
		self::SHIP2_UNBLOCK,
		self::SHIP3_UNBLOCK,
		self::SHIP4_UNBLOCK,
		self::SHIP5_UNBLOCK,
		self::SHIP6_UNBLOCK,
		self::SHIP7_UNBLOCK,
		self::SHIP8_UNBLOCK,
		self::SHIP9_UNBLOCK,
		self::SHIP10_UNBLOCK,
		self::SHIP11_UNBLOCK,
		self::COLONIZATION,
		self::CONQUEST,
	];

	public const BONUS_TECHNOLOGIES_IDS = [
		self::GENERATOR_SPEED,
		self::REFINERY_REFINING,
		self::REFINERY_STORAGE,
		self::DOCK1_SPEED,
		self::DOCK2_SPEED,
		self::TECHNOSPHERE_SPEED,
		self::COMMERCIAL_INCOME,
		self::GRAVIT_MODULE,
		self::DOCK3_SPEED,
		self::POPULATION_TAX,
		self::COMMANDER_INVEST,
		self::UNI_INVEST,
		self::ANTISPY_INVEST,
		self::SPACESHIPS_SPEED,
		self::SPACESHIPS_CONTAINER,
		self::BASE_QUANTITY,
		self::FIGHTER_SPEED,
		self::FIGHTER_ATTACK,
		self::FIGHTER_DEFENSE,
		self::CORVETTE_SPEED,
		self::CORVETTE_ATTACK,
		self::CORVETTE_DEFENSE,
		self::FRIGATE_SPEED,
		self::FRIGATE_ATTACK,
		self::FRIGATE_DEFENSE,
		self::DESTROYER_SPEED,
		self::DESTROYER_ATTACK,
		self::DESTROYER_DEFENSE,
	];

	/**
	 * @return list<int>
	 */
	public static function getAll(): array
	{
		return [
			...self::UNBLOCKING_TECHNOLOGIES_IDS,
			...self::BONUS_TECHNOLOGIES_IDS,
		];
	}
}
