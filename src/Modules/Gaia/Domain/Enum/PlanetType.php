<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Enum;

enum PlanetType: string
{
	case Desert = 'desert';
	case Gas = 'gas';
	case Ice = 'ice';
	case Ocean = 'ocean';
	case Tempered = 'tempered';
	case Tropical = 'tropical';
	case Volcanic = 'volcanic';
}
