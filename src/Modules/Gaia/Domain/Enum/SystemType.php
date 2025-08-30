<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Enum;

enum SystemType: int
{
	case Graveyard = 1;
	case Nebula = 2;
	case BlueGiant = 3;
	case YellowDwarf = 4;
	case RedDwarf = 5;
}
