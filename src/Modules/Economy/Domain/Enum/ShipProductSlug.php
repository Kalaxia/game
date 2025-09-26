<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

use App\Modules\Ares\Domain\Model\ShipCategory;

enum ShipProductSlug: string
{
	case LightFighter = 'light_fighter';
	case Fighter = 'fighter';
	case HeavyFighter = 'heavy_fighter';
	case LightCorvette = 'light_corvette';
	case Corvette = 'corvette';
	case HeavyCorvette = 'heavy_corvette';
	case LightFrigate = 'light_frigate';
	case Frigate = 'frigate';
	case Destroyer = 'destroyer';
	case HeavyDestroyer = 'heavy_destroyer';
	case Cruiser = 'cruiser';
	case HeavyCruiser = 'heavy_cruiser';

	public static function fromShipCategory(ShipCategory $shipCategory): ShipProductSlug
	{
		return match ($shipCategory) {
			ShipCategory::LightFighter => self::LightFighter,
			ShipCategory::Fighter => self::Fighter,
			ShipCategory::HeavyFighter => self::HeavyFighter,
			ShipCategory::LightCorvette => self::LightCorvette,
			ShipCategory::Corvette => self::Corvette,
			ShipCategory::HeavyCorvette => self::HeavyCorvette,
			ShipCategory::LightFrigate => self::LightFrigate,
			ShipCategory::Frigate => self::Frigate,
			ShipCategory::Destroyer => self::Destroyer,
			ShipCategory::HeavyDestroyer => self::HeavyDestroyer,
			ShipCategory::Cruiser => self::Cruiser,
			ShipCategory::HeavyCruiser => self::HeavyCruiser,
		};
	}
}
