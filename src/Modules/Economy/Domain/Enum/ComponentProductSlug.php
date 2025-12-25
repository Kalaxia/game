<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum ComponentProductSlug: string
{
	case LightHydrogenPropulsor = 'light_hydrogen_propulsor';
	case TritiumPropulsor = 'tritium_propulsor';
	case UraniumPropulsor = 'uranium_propulsor';
	case HeavyUraniumPropulsor = 'heavy_uranium_propulsor';
	case SmallLaserCannon = 'small_laser_cannon';
	case LaserCannon = 'laser_cannon';
	case LaserTurret = 'laser_turret';
	case LaserBattery = 'laser_battery';
	case HeavyLaserCannon = 'heavy_laser_cannon';
	case NeutronShieldGenerator = 'neutron_shield_generator';
	case PlasmaShieldGenerator = 'plasma_shield_generator';
	case HeavyPlasmaShieldGenerator = 'heavy_plasma_shield_generator';
	case MissileLauncher = 'missile_launcher';
	case TorpedoLauncher = 'torpedo_launcher';

	public static function fromComponentType(ComponentType $type): array
	{
		return match ($type) {
			ComponentType::Propulsor => [
				self::LightHydrogenPropulsor,
				self::TritiumPropulsor,
				self::UraniumPropulsor,
				self::HeavyUraniumPropulsor,
			],
			ComponentType::Weapon => [
				self::SmallLaserCannon,
				self::LaserCannon,
				self::LaserTurret,
				self::LaserBattery,
				self::HeavyLaserCannon,
				self::MissileLauncher,
				self::TorpedoLauncher,
			],
			ComponentType::ShieldGenerator => [
				self::NeutronShieldGenerator,
				self::PlasmaShieldGenerator,
				self::HeavyPlasmaShieldGenerator,
			],
		};
	}
}
