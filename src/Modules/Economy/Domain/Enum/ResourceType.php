<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum ResourceType: string
{
	// Minerals
	case Carbon = 'carbon';
	case Cobalt = 'cobalt';
	case Rhenium = 'rhenium';
	case Titanium = 'titanium';
	case Platinum = 'platinum';
	case Crystal = 'crystal';
	case Uranium = 'uranium';
	// Gas
	case Deuterium = 'deuterium';
	case Tritium = 'tritium';
	case Hydrogen = 'hydrogen';
	case Helium = 'helium';
	case Nitrogen = 'nitrogen';

	// Food
	case Cereals = 'cereals';
	case Fruits = 'fruits';
	case Fish = 'fish';
	case Meat = 'meat';

	// Luxury products
	case Coffee = 'coffee';
	case Tea = 'tea';
	case Salt = 'salt';
	case Sugar = 'sugar';
	case Wine = 'wine';
	case Beer = 'beer';
	case Digestive = 'digestive';

	public static function fromActivity(Activity $activity): self
	{
		return match ($activity) {
			Activity::CarbonExtractor => self::Carbon,
			Activity::CobaltMine => self::Cobalt,
			Activity::RheniumExtractor => self::Rhenium,
			Activity::UraniumMine => self::Uranium,
			Activity::TeaFarm => self::Tea,
			Activity::PlatinumMine => self::Platinum,
			Activity::CrystalMine => self::Crystal,
			Activity::CoffeeFarm => self::Coffee,
			Activity::FishingPort => self::Fish,
			Activity::Farm => self::Cereals,
			Activity::Orchard => self::Fruits,
			Activity::SaltFarm => self::Salt,
			Activity::SugarFarm => self::Sugar,
			Activity::Vineyard => self::Wine,
			Activity::Brewery => self::Digestive,
			Activity::Breeding => self::Meat,
			Activity::DeuteriumExtractor => self::Deuterium,
			Activity::TritiumExtractor => self::Tritium,
			Activity::HydrogenExtractor => self::Hydrogen,
			Activity::HeliumExtractor => self::Helium,
			Activity::NitrogenExtractor => self::Nitrogen,
			default => throw new \LogicException(sprintf(
				'Activity %s does not produce resources',
				$activity->name,
			)),
		};
	}
}
