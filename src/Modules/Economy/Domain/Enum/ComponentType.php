<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum ComponentType: string
{
	case Propulsor = 'propulsor';
	case ShieldGenerator = 'shield_generator';
	case Weapon = 'weapon';

	public static function fromActivity(Activity $activity): self
	{
		return match ($activity) {
			Activity::PropulsorFactory => self::Propulsor,
			Activity::ShieldFactory => self::ShieldGenerator,
			Activity::WeaponFactory => self::Weapon,
			default => throw new \LogicException(sprintf(
				'Activity %s does not produce components',
				$activity->name,
			)),
		};
	}
}
