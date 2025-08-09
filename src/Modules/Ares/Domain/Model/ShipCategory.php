<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model;

enum ShipCategory: int
{
	case LightFighter = 0;
	case Fighter = 1;
	case HeavyFighter = 2;
	case LightCorvette = 3;
	case Corvette = 4;
	case HeavyCorvette = 5;
	case LightFrigate = 6;
	case Frigate = 7;
	case Destroyer = 8;
	case HeavyDestroyer = 9;
	case Cruiser = 10;
	case HeavyCruiser = 11;
}
