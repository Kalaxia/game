<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

/**
 * Size class of a ship, matching the `class` field of the ship categories configuration.
 */
enum ShipClass: int
{
	case Fighter = 0;
	case Corvette = 1;
	case Frigate = 2;
	case Destroyer = 3;
}
