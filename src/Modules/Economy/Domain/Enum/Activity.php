<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum Activity: string {
	case WheatFarm = 'weathfarm';
	case TitaniumMine = 'titanium_mine';
	case CrystalMine = 'crystal_mine';
	case DeuteriumExtractor = 'deuterium_extractor';
	case TritiumExtractor = 'tritium_extractor';
	case HydrogenExtractor = 'hydrogen_extractor';

	case PropulsorFactory = 'propulsor_factory';
	case WeaponFactory = 'weapon_factory';
	case ShieldFactory = 'shield_factory';
	case Shipyard = 'shipyard';

	case Narcotrafic = 'narcotrafic';
	case Transporter = 'transporter';
}
