<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ResourceType;

return static function (ContainerConfigurator $container): void {
	$container->extension('industry_schemas', [
		'ships' => [
			[
				'slug' => 'light_fighter',
				'ship_category' => ShipCategory::LightFighter,
				'requirements' => [
					[
						'slug' => 'light_hydrogen_propulsor',
						'quantity' => 1,
					],
					[
						'slug' => 'small_laser_cannon',
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => 'fighter',
				'ship_category' => ShipCategory::Fighter,
				'requirements' => [
					[
						'slug' => 'light_hydrogen_propulsor',
						'quantity' => 2,
					],
					[
						'slug' => 'neutron_shield_generator',
						'quantity' => 1,
					],
					[
						'slug' => 'laser_cannon',
						'quantity' => 1,
					]
				],
			],
			[
				'slug' => 'heavy_fighter',
				'ship_category' => ShipCategory::HeavyFighter,
				'requirements' => [
					[
						'slug' => 'missile_launcher',
						'quantity' => 1,
					],
					[
						'slug' => 'laser_cannon',
						'quantity' => 2,
					],
					[
						'slug' => 'neutron_shield_generator',
						'quantity' => 1,
					],
					[
						'slug' => 'tritium_propulsor',
						'quantity' => 1,
					],
				],
			],
			[
				'slug' => 'light_corvette',
				'ship_category' => ShipCategory::LightCorvette,
				'requirements' => [
					[
						'slug' => 'missile_launcher',
						'quantity' => 2,
					],
					[
						'slug' => 'laser_cannon',
						'quantity' => 2,
					],
					[
						'slug' => 'neutron_shield_generator',
						'quantity' => 2,
					],
					[
						'slug' => 'tritium_propulsor',
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => 'corvette',
				'ship_category' => ShipCategory::Corvette,
				'requirements' => [
					[
						'slug' => 'missile_launcher',
						'quantity' => 2,
					],
					[
						'slug' => 'laser_turret',
						'quantity' => 1,
					],
					[
						'slug' => 'plasma_shield_generator',
						'quantity' => 1,
					],
					[
						'slug' => 'tritium_propulsor',
						'quantity' => 2,
					],
				],
			],
			[
				'slug' => 'heavy_corvette',
				'ship_category' => ShipCategory::HeavyCorvette,
				'requirements' => [
					[
						[
							'slug' => 'missile_launcher',
							'quantity' => 4,
						],
						[
							'slug' => 'laser_turret',
							'quantity' => 2,
						],
						[
							'slug' => 'plasma_shield_generator',
							'quantity' => 1,
						],
						[
							'slug' => 'tritium_propulsor',
							'quantity' => 2,
						],
					],
				],
			],
			[
				'slug' => 'light_frigate',
				'ship_category' => ShipCategory::LightFrigate,
				'requirements' => [
					[
						[
							'slug' => 'laser_turret',
							'quantity' => 4,
						],
						[
							'slug' => 'laser_battery',
							'quantity' => 2,
						],
						[
							'slug' => 'tritium_propulsor',
							'quantity' => 3,
						],
						[
							'slug' => 'plasma_shield_generator',
							'quantity' => 2,
						],
					],
				],
			],
			[
				'slug' => 'frigate',
				'ship_category' => ShipCategory::Frigate,
				'requirements' => [
					[
						[
							'slug' => 'laser_turret',
							'quantity' => 6,
						],
						[
							'slug' => 'laser_battery',
							'quantity' => 4,
						],
						[
							'slug' => 'tritium_propulsor',
							'quantity' => 3,
						],
						[
							'slug' => 'plasma_shield_generator',
							'quantity' => 2,
						],
					],
				],
			],
			[
				'slug' => 'destroyer',
				'ship_category' => ShipCategory::Destroyer,
				'requirements' => [
					[
						[
							'slug' => 'missile_launcher',
							'quantity' => 6,
						],
						[
							'slug' => 'laser_turret',
							'quantity' => 4,
						],
						[
							'slug' => 'laser_battery',
							'quantity' => 4,
						],
						[
							'slug' => 'uranium_propulsor',
							'quantity' => 3,
						],
						[
							'slug' => 'heavy_plasma_shield_generator',
							'quantity' => 2,
						],
					],
				],
			],
			[
				'slug' => 'heavy_destroyer',
				'ship_category' => ShipCategory::HeavyDestroyer,
				'requirements' => [
					[
						[
							'slug' => 'torpedo_launcher',
							'quantity' => 2,
						],
						[
							'slug' => 'missile_launcher',
							'quantity' => 4,
						],
						[
							'slug' => 'laser_turret',
							'quantity' => 6,
						],
						[
							'slug' => 'heavy_laser_cannon',
							'quantity' => 1,
						],
						[
							'slug' => 'laser_battery',
							'quantity' => 6,
						],
						[
							'slug' => 'uranium_propulsor',
							'quantity' => 4,
						],
						[
							'slug' => 'heavy_plasma_shield_generator',
							'quantity' => 4,
						],
					],
				],
			],
			[
				'slug' => 'cruiser',
				'ship_category' => ShipCategory::Cruiser,
				'requirements' => [
					[
						[
							'slug' => 'torpedo_launcher',
							'quantity' => 4,
						],
						[
							'slug' => 'laser_turret',
							'quantity' => 6,
						],
						[
							'slug' => 'heavy_laser_cannon',
							'quantity' => 2,
						],
						[
							'slug' => 'laser_battery',
							'quantity' => 8,
						],
						[
							'slug' => 'uranium_propulsor',
							'quantity' => 4,
						],
						[
							'slug' => 'heavy_plasma_shield_generator',
							'quantity' => 4,
						],
					],
				],
			],
			[
				'slug' => 'heavy_cruiser',
				'ship_category' => ShipCategory::HeavyCruiser,
				'requirements' => [
					[
						[
							'slug' => 'torpedo_launcher',
							'quantity' => 6,
						],
						[
							'slug' => 'laser_turret',
							'quantity' => 6,
						],
						[
							'slug' => 'heavy_laser_cannon',
							'quantity' => 4,
						],
						[
							'slug' => 'laser_battery',
							'quantity' => 10,
						],
						[
							'slug' => 'uranium_propulsor',
							'quantity' => 4,
						],
						[
							'slug' => 'heavy_uranium_propulsor',
							'quantity' => 1,
						],
						[
							'slug' => 'heavy_plasma_shield_generator',
							'quantity' => 6,
						],
					],
				],
			],
		],
		'components' => [
			[
				'slug' => 'light_hydrogen_propulsor',
				'type' => ComponentType::Propulsor,
				'requirements' => [
					[
						'type' => ResourceType::Hydrogen,
						'quantity' => 1000,
					],
					[
						'type' => ResourceType::Titanium,
						'quantity' => 200,
					],
				],
			],
			[
				'slug' => 'tritium_propulsor',
				'type' => ComponentType::Propulsor,
				'requirements' => [
					[
						'type' => ResourceType::Tritium,
						'quantity' => 800,
					],
					[
						'type' => ResourceType::Titanium,
						'quantity' => 250,
					],
				],
			],
			[
				'slug' => 'uranium_propulsor',
				'type' => ComponentType::Propulsor,
				'requirements' => [
					[
						'type' => ResourceType::Uranium,
						'quantity' => 500,
					],
					[
						'type' => ResourceType::Nitrogen,
						'quantity' => 300,
					],
					[
						'type' => ResourceType::Hydrogen,
						'quantity' => 250,
					],
					[
						'type' => ResourceType::Titanium,
						'quantity' => 500,
					],
				],
			],
			[
				'slug' => 'heavy_uranium_propulsor',
				'type' => ComponentType::Propulsor,
				'requirements' => [
					[
						'type' => ResourceType::Uranium,
						'quantity' => 800,
					],
					[
						'type' => ResourceType::Nitrogen,
						'quantity' => 500,
					],
					[
						'type' => ResourceType::Hydrogen,
						'quantity' => 400,
					],
					[
						'type' => ResourceType::Titanium,
						'quantity' => 800,
					],
				],
			],
			[
				'slug' => 'small_laser_cannon',
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 250,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 100,
					],
				],
			],
			[
				'slug' => 'laser_cannon',
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 350,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 150,
					],
				],
			],
			[
				'slug' => 'laser_turret',
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 400,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 100,
					],
					[
						'type' => ResourceType::Carbon,
						'quantity' => 150,
					],
				],
			],
			[
				'slug' => 'heavy_laser_cannon',
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 750,
					],
					[
						'type' => ResourceType::Platinum,
						'quantity' => 300,
					],
					[
						'type' => ResourceType::Carbon,
						'quantity' => 400,
					],
				],
			],
			[
				'slug' => 'missile_launcher',
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Rhenium,
						'quantity' => 250,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 100,
					],
					[
						'type' => ResourceType::Deuterium,
						'quantity' => 100,
					],
				],
			],
			[
				'slug' => 'torpedo_launcher',
				'type' => ComponentType::Weapon,
				'requirements' => [
					[
						'type' => ResourceType::Rhenium,
						'quantity' => 400,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 200,
					],
					[
						'type' => ResourceType::Deuterium,
						'quantity' => 200,
					],
					[
						'type' => ResourceType::Uranium,
						'quantity' => 200,
					],
				],
			],
			[
				'slug' => 'neutron_shield_generator',
				'type' => ComponentType::ShieldGenerator,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 350,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 150,
					],
				],
			],
			[
				'slug' => 'plasma_shield_generator',
				'type' => ComponentType::ShieldGenerator,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 450,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 150,
					],
					[
						'type' => ResourceType::Deuterium,
						'quantity' => 100,
					],
					[
						'type' => ResourceType::Nitrogen,
						'quantity' => 100,
					],
				],
			],
			[
				'slug' => 'heavy_plasma_shield_generator',
				'type' => ComponentType::ShieldGenerator,
				'requirements' => [
					[
						'type' => ResourceType::Crystal,
						'quantity' => 650,
					],
					[
						'type' => ResourceType::Cobalt,
						'quantity' => 250,
					],
					[
						'type' => ResourceType::Deuterium,
						'quantity' => 300,
					],
					[
						'type' => ResourceType::Nitrogen,
						'quantity' => 350,
					],
				],
			],
		],
	]);
};
