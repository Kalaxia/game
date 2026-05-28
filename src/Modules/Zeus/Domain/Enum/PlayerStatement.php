<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Enum;

enum PlayerStatement: int
{
	case Active = 1;
	case Inactive = 2;
	case Holiday = 3;
	case Banned = 4;
	case Deleted = 5;
	case Dead = 6;
}
