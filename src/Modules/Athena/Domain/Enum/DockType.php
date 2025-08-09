<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Enum;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Zeus\Model\PlayerBonusId;

enum DockType: string
{
	case Manufacture = 'manufacture';
	case Shipyard = 'shipyard';

	public function getShipRange(): array
	{
		return match ($this) {
			self::Manufacture => range(ShipCategory::LightFighter->value, ShipCategory::HeavyCorvette->value),
			self::Shipyard => range(ShipCategory::LightFrigate->value, ShipCategory::HeavyCruiser->value),
		};
	}

	public function getLevel(OrbitalBase $base): int
	{
		return match ($this) {
			self::Manufacture => $base->levelDock1,
			self::Shipyard => $base->levelDock2,
		};
	}

	public function getBuildingNumber(): int
	{
		return match ($this) {
			self::Manufacture => OrbitalBaseResource::DOCK1,
			self::Shipyard => OrbitalBaseResource::DOCK2,
		};
	}

	public function getSpeedBonusId(): int
	{
		return match ($this) {
			self::Manufacture => PlayerBonusId::DOCK1_SPEED,
			self::Shipyard => PlayerBonusId::DOCK2_SPEED,
		};
	}

	public function getIdentifier(): int
	{
		return match ($this) {
			self::Manufacture => 1,
			self::Shipyard => 2,
		};
	}

	public static function fromShipCategory(ShipCategory $category): self
	{
		return match ($category) {
			ShipCategory::LightFighter, ShipCategory::Fighter, ShipCategory::HeavyFighter,
			ShipCategory::Corvette, ShipCategory::LightCorvette, ShipCategory::HeavyCorvette => self::Manufacture,
			ShipCategory::LightFrigate, ShipCategory::Frigate,
			ShipCategory::Destroyer, ShipCategory::HeavyDestroyer,
			ShipCategory::Cruiser, ShipCategory::HeavyCruiser => self::Shipyard,
			default => throw new \InvalidArgumentException(sprintf('Invalid ship category %d', $category->value)),
		};
	}
}
