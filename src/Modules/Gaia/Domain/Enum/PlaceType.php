<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Enum;

enum PlaceType: int
{
	case Planet = 1;
	case GasPlanet = 2;
	case Ruin = 3;
	case GasPocket = 4;
	case Asteroid = 5;
	case Empty = 6;
}
