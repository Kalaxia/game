<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum ComponentType: string
{
	case Propulsor = 'propulsor';
	case ShieldGenerator = 'shield_generator';
	case Weapon = 'weapon';
}
