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

	public function getPlacesProportions(): array
	{
		return match ($this) {
			self::Graveyard => [0, 0, 85, 10, 0, 5],
			self::Nebula => [0, 0, 5, 90, 0, 5],
			self::BlueGiant => [60, 20, 2, 0, 15, 3],
			self::YellowDwarf => [65, 15, 3, 0, 15, 2],
			self::RedDwarf => [75, 10, 3, 0, 10, 2],
		};
	}

	public function getPlacesCountRange(): array
	{
		return match ($this) {
			self::Graveyard => [2, 6],
			self::Nebula => [2, 8],
			self::BlueGiant => [8, 12],
			self::YellowDwarf => [6, 10],
			self::RedDwarf => [3, 6],
		};
	}
}
