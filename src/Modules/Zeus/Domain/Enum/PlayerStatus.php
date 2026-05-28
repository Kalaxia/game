<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Enum;

enum PlayerStatus: int
{
	case Standard = 1;
	case Parliament = 2;
	case Treasurer = 3;
	case Warlord = 4;
	case Minister = 5;
	case Chief = 6;
}
