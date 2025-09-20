<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Enum;

use App\Modules\Economy\Domain\Enum\ResourceType;

enum PlanetType: string
{
	case Desert = 'desert';
	case Gas = 'gas';
	case Ice = 'ice';
	case Ocean = 'ocean';
	case Tempered = 'tempered';
	case Tropical = 'tropical';
	case Volcanic = 'volcanic';

	public function getPopulationBaseRange(): array
	{
		return match ($this) {
			self::Desert => [1, 400],
			self::Gas => [5, 300],
			self::Ice => [10, 200],
			self::Ocean => [10, 200],
			self::Tempered => [100, 2000],
			self::Tropical => [50, 1500],
			self::Volcanic => [1, 200],
		};
	}

	public function getNaturalResourcesBaseCoefficients(): array
	{
		return match ($this) {
			self::Desert => [
				ResourceType::Carbon->value => 30,
				ResourceType::Crystal->value => 50,
				ResourceType::Uranium->value => 30,
				ResourceType::Rhenium->value => 20,
				ResourceType::Platinum->value => 30,
				ResourceType::Titanium->value => 40,
			],
			self::Gas => [
				ResourceType::Hydrogen->value => 70,
				ResourceType::Helium->value => 60,
				// TODO Nitrogen is not present in gas planet environments
				ResourceType::Nitrogen->value => 40,
				ResourceType::Deuterium->value => 40,
				ResourceType::Tritium->value => 35,
			],
			self::Ice => [
				ResourceType::Hydrogen->value => 20,
				ResourceType::Nitrogen->value => 10,
				ResourceType::Deuterium->value => 10,
				ResourceType::Tritium->value => 10,
				ResourceType::Meat->value => 10,
				ResourceType::Fish->value => 30,
				ResourceType::Salt->value => 20,
			],
			self::Ocean => [
				ResourceType::Hydrogen->value => 30,
				ResourceType::Nitrogen->value => 50,
				ResourceType::Fish->value => 70,
				ResourceType::Salt->value => 60,
			],
			self::Tempered => [
				ResourceType::Hydrogen->value => 10,
				ResourceType::Nitrogen->value => 10,
				ResourceType::Cereals->value => 60,
				ResourceType::Fruits->value => 60,
				ResourceType::Meat->value => 60,
				ResourceType::Fish->value => 40,
				ResourceType::Salt->value => 20,
				ResourceType::Coffee->value => 20,
				ResourceType::Tea->value => 20,
			],
			self::Tropical => [
				ResourceType::Hydrogen->value => 20,
				ResourceType::Cereals->value => 20,
				ResourceType::Fruits->value => 40,
				ResourceType::Meat->value => 40,
				ResourceType::Coffee->value => 50,
				ResourceType::Tea->value => 50,
			],
			self::Volcanic => [
				ResourceType::Carbon->value => 50,
				ResourceType::Crystal->value => 50,
				ResourceType::Cobalt->value => 50,
				ResourceType::Titanium->value => 50,
				ResourceType::Uranium->value => 20,
				ResourceType::Rhenium->value => 20,
			],
		};
	}
}
