<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;

return static function (ContainerConfigurator $container): void {
	$container->extension('industry_schemas', [
		'ships' => [
			[
				'slug' => ShipProductSlug::LightFighter,
				'ship_category' => ShipCategory::LightFighter,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::LightHydrogenPropulsor,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::SmallLaserCannon,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ShipProductSlug::Fighter,
				'ship_category' => ShipCategory::Fighter,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::LightHydrogenPropulsor,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::NeutronShieldGenerator,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::LaserCannon,
						'quantity' => 1,
					]
				],
			],
			[
				'slug' => ShipProductSlug::HeavyFighter,
				'ship_category' => ShipCategory::HeavyFighter,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::MissileLauncher,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::LaserCannon,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::NeutronShieldGenerator,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::TritiumPropulsor,
						'quantity' => 1,
					],
				],
			],
			[
				'slug' => ShipProductSlug::LightCorvette,
				'ship_category' => ShipCategory::LightCorvette,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::MissileLauncher,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::LaserCannon,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::NeutronShieldGenerator,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::TritiumPropulsor,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ShipProductSlug::Corvette,
				'ship_category' => ShipCategory::Corvette,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::MissileLauncher,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::LaserTurret,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::PlasmaShieldGenerator,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::TritiumPropulsor,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ShipProductSlug::HeavyCorvette,
				'ship_category' => ShipCategory::HeavyCorvette,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::MissileLauncher,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::LaserTurret,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::PlasmaShieldGenerator,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::TritiumPropulsor,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ShipProductSlug::LightFrigate,
				'ship_category' => ShipCategory::LightFrigate,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::LaserTurret,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::LaserBattery,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::TritiumPropulsor,
						'quantity' => 3,
					],
					[
						'slug' => ComponentProductSlug::PlasmaShieldGenerator,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ShipProductSlug::Frigate,
				'ship_category' => ShipCategory::Frigate,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::LaserTurret,
						'quantity' => 6,
					],
					[
						'slug' => ComponentProductSlug::LaserBattery,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::TritiumPropulsor,
						'quantity' => 3,
					],
					[
						'slug' => ComponentProductSlug::PlasmaShieldGenerator,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ShipProductSlug::Destroyer,
				'ship_category' => ShipCategory::Destroyer,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::MissileLauncher,
						'quantity' => 6,
					],
					[
						'slug' => ComponentProductSlug::LaserTurret,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::LaserBattery,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::UraniumPropulsor,
						'quantity' => 3,
					],
					[
						'slug' => ComponentProductSlug::HeavyPlasmaShieldGenerator,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ShipProductSlug::HeavyDestroyer,
				'ship_category' => ShipCategory::HeavyDestroyer,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::TorpedoLauncher,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::MissileLauncher,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::LaserTurret,
						'quantity' => 6,
					],
					[
						'slug' => ComponentProductSlug::HeavyLaserCannon,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::LaserBattery,
						'quantity' => 6,
					],
					[
						'slug' => ComponentProductSlug::UraniumPropulsor,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::HeavyPlasmaShieldGenerator,
						'quantity' => 4,
					],
				],
			],
			[
				'slug' => ShipProductSlug::Cruiser,
				'ship_category' => ShipCategory::Cruiser,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::TorpedoLauncher,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::LaserTurret,
						'quantity' => 6,
					],
					[
						'slug' => ComponentProductSlug::HeavyLaserCannon,
						'quantity' => 2,
					],
					[
						'slug' => ComponentProductSlug::LaserBattery,
						'quantity' => 8,
					],
					[
						'slug' => ComponentProductSlug::UraniumPropulsor,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::HeavyPlasmaShieldGenerator,
						'quantity' => 4,
					],
				],
			],
			[
				'slug' => ShipProductSlug::HeavyCruiser,
				'ship_category' => ShipCategory::HeavyCruiser,
				'requirements' => [
					[
						'slug' => ComponentProductSlug::TorpedoLauncher,
						'quantity' => 6,
					],
					[
						'slug' => ComponentProductSlug::LaserTurret,
						'quantity' => 6,
					],
					[
						'slug' => ComponentProductSlug::HeavyLaserCannon,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::LaserBattery,
						'quantity' => 10,
					],
					[
						'slug' => ComponentProductSlug::UraniumPropulsor,
						'quantity' => 4,
					],
					[
						'slug' => ComponentProductSlug::HeavyUraniumPropulsor,
						'quantity' => 1,
					],
					[
						'slug' => ComponentProductSlug::HeavyPlasmaShieldGenerator,
						'quantity' => 6,
					],
				],
			],
		],
		'components' => [
			[
				'slug' => ComponentProductSlug::LightHydrogenPropulsor,
				'type' => ComponentType::Propulsor,
				'requirements' => [
					[
						'type' => ResourceType::Hydrogen,
						'quantity' => 10,
					],
					[
						'type' => ResourceType::Titanium,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::TritiumPropulsor,
				'type' => ComponentType::Propulsor,
				'requirements' => [
					[
						'type' => ResourceType::Tritium,
						'quantity' => 8,
					],
					[
						'type' => ResourceType::Titanium,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::UraniumPropulsor,
				'type' => ComponentType::Propulsor,
				'requirements' => [
					[
						'type' => ResourceType::Uranium,
						'quantity' => 5,
					],
					[
						'type' => ResourceType::Nitrogen,
						'quantity' => 3,
					],
					[
						'type' => ResourceType::Hydrogen,
						'quantity' => 2,
					],
					[
						'type' => ResourceType::Titanium,
						'quantity' => 5,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::HeavyUraniumPropulsor,
				'type' => ComponentType::Propulsor,
				'requirements' => [
					[
						'type' => ResourceType::Uranium,
						'quantity' => 8,
					],
					[
						'type' => ResourceType::Nitrogen,
						'quantity' => 5,
					],
					[
						'type' => ResourceType::Hydrogen,
						'quantity' => 4,
					],
					[
						'type' => ResourceType::Titanium,
						'quantity' => 8,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::SmallLaserCannon,
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 2,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 1,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::LaserCannon,
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 3,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 1,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::LaserTurret,
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 4,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 1,
					],
					[
						'type' => ResourceType::Carbon,
						'quantity' => 1,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::LaserBattery,
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 5,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 2,
					],
					[
						'type' => ResourceType::Carbon,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::HeavyLaserCannon,
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 7,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 3,
					],
					[
						'type' => ResourceType::Carbon,
						'quantity' => 4,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::MissileLauncher,
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Rhenium,
						'quantity' => 2,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 1,
					],
					[
						'type' => ResourceType::Deuterium,
						'quantity' => 1,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::TorpedoLauncher,
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Rhenium,
						'quantity' => 4,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 2,
					],
					[
						'type' => ResourceType::Deuterium,
						'quantity' => 2,
					],
					[
						'type' => ResourceType::Uranium,
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::NeutronShieldGenerator,
				'type' => ComponentType::ShieldGenerator,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 3,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 1,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::PlasmaShieldGenerator,
				'type' => ComponentType::ShieldGenerator,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 4,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 1,
					],
					[
						'type' => ResourceType::Deuterium,
						'quantity' => 1,
					],
					[
						'type' => ResourceType::Nitrogen,
						'quantity' => 1,
					],
				],
			],
			[
				'slug' => ComponentProductSlug::HeavyPlasmaShieldGenerator,
				'type' => ComponentType::ShieldGenerator,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 6,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 2,
					],
					[
						'type' => ResourceType::Deuterium,
						'quantity' => 3,
					],
					[
						'type' => ResourceType::Nitrogen,
						'quantity' => 3,
					],
				],
			],
		],
	]);
};
