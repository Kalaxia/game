<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum Activity: string
{
	case Breeding = 'breeding';
	case Farm = 'farm';
	case FishingPort = 'fishing_port';

	case CoffeeFarm = 'coffee_farm';
	case TeaFarm = 'tea_farm';
	case SaltFarm = 'salt_farm';
	case SugarFarm = 'sugar_farm';
	case Orchard = 'orchard';
	case TitaniumMine = 'titanium_mine';
	case PlatinumMine = 'platinum_mine';
	case CrystalMine = 'crystal_mine';
	case UraniumMine = 'uranium_mine';
	case CobaltMine = 'cobalt_mine';
	case DeuteriumExtractor = 'deuterium_extractor';
	case TritiumExtractor = 'tritium_extractor';
	case HydrogenExtractor = 'hydrogen_extractor';
	case HeliumExtractor = 'helium_extractor';
	case NitrogenExtractor = 'nitrogen_extractor';
	case RheniumExtractor = 'rhenium_extractor';
	case CarbonExtractor = 'carbon_extractor';

	case PropulsorFactory = 'propulsor_factory';
	case WeaponFactory = 'weapon_factory';
	case ShieldFactory = 'shield_factory';
	case Shipyard = 'shipyard';

	// case Narcotrafic = 'narcotrafic';
	// case Transporter = 'transporter';
	case Brewery = 'brewery';
	case Vineyard = 'vineyard';

	public function getCategory(): ActivityCategory
	{
		return match ($this) {
			self::Breeding, self::Farm, self::SaltFarm, self::CoffeeFarm, self::TeaFarm,
			self::SugarFarm, self::Orchard, self::FishingPort,
			self::CobaltMine, self::PlatinumMine, self::TitaniumMine, self::CrystalMine,
			self::CarbonExtractor, self::UraniumMine, self::HydrogenExtractor, self::HeliumExtractor => ActivityCategory::PrimaryProduction,
			self::NitrogenExtractor, self::DeuteriumExtractor, self::TritiumExtractor, self::RheniumExtractor, => ActivityCategory::Refinement,
			self::WeaponFactory, self::PropulsorFactory, self::ShieldFactory,
			self::Shipyard, self::Brewery, self::Vineyard => ActivityCategory::FinalProduction,
			default => throw new \LogicException(sprintf('Activity %s is not linked to a category', $this->value)),
		};
	}

	public function getCost(): int
	{
		return 1000000;
	}

	/**
	 * @return array{
	 *     local?: list<ResourceType>,
	 *     any?: list<ResourceType|ComponentType>,
	 * }
	 */
	public function getDependencies(): array
	{
		return match ($this) {
			self::FishingPort => [
				'local' => [ResourceType::Fish],
			],
			self::Breeding => [
				'local' => [ResourceType::Meat],
			],
			self::Farm => [
				'local' => [ResourceType::Cereals],
			],
			self::CoffeeFarm => [
				'local' => [ResourceType::Coffee],
			],
			self::TeaFarm => [
				'local' => [ResourceType::Tea],
			],
			self::SaltFarm => [
				'local' => [ResourceType::Salt],
			],
			self::SugarFarm => [
				'local' => [ResourceType::Sugar],
			],
			self::CobaltMine => [
				'local' => [ResourceType::Cobalt],
			],
			self::UraniumMine => [
				'local' => [ResourceType::Uranium],
			],
			self::Orchard => [
				'local' => [ResourceType::Fruits],
			],
			self::TitaniumMine => [
				'local' => [ResourceType::Titanium],
			],
			self::PlatinumMine => [
				'local' => [ResourceType::Platinum],
			],
			self::CarbonExtractor => [
				'local' => [ResourceType::Carbon],
			],
			self::CrystalMine => [
				'local' => [ResourceType::Crystal],
			],
			self::DeuteriumExtractor => [
				'any' => [ResourceType::Hydrogen],
				'local' => [ResourceType::Deuterium],
			],
			self::TritiumExtractor => [
				'any' => [ResourceType::Hydrogen],
				'local' => [ResourceType::Tritium],
			],
			self::HeliumExtractor => [
				'local' => [ResourceType::Helium],
			],
			self::HydrogenExtractor => [
				'local' => [ResourceType::Hydrogen],
			],
			self::RheniumExtractor => [
				'any' => [ResourceType::Rhenium],
			],
			self::NitrogenExtractor => [
				'any' => [ResourceType::Hydrogen],
				'local' => [ResourceType::Nitrogen],
			],
			self::PropulsorFactory => [
				'any' => [
					ResourceType::Hydrogen,
					ResourceType::Tritium,
					ResourceType::Deuterium,
					ResourceType::Nitrogen,
					ResourceType::Helium,
				],
			],
			self::ShieldFactory => [
				'any' => [
					ResourceType::Crystal,
					ResourceType::Cobalt,
					ResourceType::Carbon,
					ResourceType::Hydrogen,
				],
			],
			self::WeaponFactory => [
				'any' => [
					ResourceType::Crystal,
					ResourceType::Rhenium,
					ResourceType::Tritium,
					ResourceType::Uranium,
					ResourceType::Carbon,
				],
			],
			self::Shipyard => [
				'any' => [
					ResourceType::Platinum,
					ResourceType::Titanium,
					ComponentType::Weapon,
					ComponentType::Propulsor,
					componentType::ShieldGenerator,
				],
			],
			self::Brewery => [
				'any' => [
					ResourceType::Cereals,
					ResourceType::Fruits,
				],
			],
			self::Vineyard => [
				'any' => [
					ResourceType::Fruits,
				],
			],
			default => [],
		};
	}
}
