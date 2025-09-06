<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Enum;

enum SystemType: int
{
	case Graveyard = 1;
	case Nebula = 2;
	case BlueGiant = 3;
	case YellowDwarf = 4;
	case RedDwarf = 5;

	public function getPlanetTypeProportions(): array
	{
		return match ($this) {
			self::Graveyard => [
				PlanetType::Desert->name => 65,
				PlanetType::Gas->name => 20,
				PlanetType::Ice->name => 5,
				PlanetType::Ocean->name => 0,
				PlanetType::Tempered->name => 0,
				PlanetType::Tropical->name => 0,
				PlanetType::Volcanic->name => 10,
			],
			self::Nebula => [
				PlanetType::Desert->name => 10,
				PlanetType::Gas->name => 40,
				PlanetType::Ice->name => 0,
				PlanetType::Ocean->name => 10,
				PlanetType::Tempered->name => 5,
				PlanetType::Tropical->name => 5,
				PlanetType::Volcanic->name => 30,
			],
			self::BlueGiant => [
				PlanetType::Desert->name => 5,
				PlanetType::Gas->name => 0,
				PlanetType::Ice->name => 20,
				PlanetType::Ocean->name => 20,
				PlanetType::Tempered->name => 25,
				PlanetType::Tropical->name => 25,
				PlanetType::Volcanic->name => 5,
			],
			self::YellowDwarf => [
				PlanetType::Desert->name => 20,
				PlanetType::Gas->name => 20,
				PlanetType::Ice->name => 5,
				PlanetType::Ocean->name => 10,
				PlanetType::Tempered->name => 20,
				PlanetType::Tropical->name => 25,
				PlanetType::Volcanic->name => 0,
			],
			self::RedDwarf => [
				PlanetType::Desert->name => 5,
				PlanetType::Gas->name => 5,
				PlanetType::Ice->name => 20,
				PlanetType::Ocean->name => 20,
				PlanetType::Tempered->name => 30,
				PlanetType::Tropical->name => 15,
				PlanetType::Volcanic->name => 5,
			],
		};
	}

	public function getPlacesProportions(): array
	{
		return match ($this) {
			self::Graveyard => [0, 0, 85, 10, 0, 5],
			self::Nebula => [0, 0, 5, 90, 0, 5],
			self::BlueGiant => [60, 20, 2, 0, 15, 3],
			self::YellowDwarf => [65, 15, 3, 0, 15, 2],
			self::RedDwarf => [75, 10, 3, 0, 10, 2],
		};
	}

	public function getPlacesCountRange(): array
	{
		return match ($this) {
			self::Graveyard => [2, 6],
			self::Nebula => [2, 8],
			self::BlueGiant => [8, 12],
			self::YellowDwarf => [6, 10],
			self::RedDwarf => [3, 6],
		};
	}
}
