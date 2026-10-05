<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle\Grid;

/**
 * The six directions of a pointy-top hex grid, clockwise from east.
 */
enum HexDirection: int
{
	case East = 0;
	case SouthEast = 1;
	case SouthWest = 2;
	case West = 3;
	case NorthWest = 4;
	case NorthEast = 5;

	public function toHex(): Hex
	{
		return match ($this) {
			self::East => new Hex(1, 0),
			self::SouthEast => new Hex(0, 1),
			self::SouthWest => new Hex(-1, 1),
			self::West => new Hex(-1, 0),
			self::NorthWest => new Hex(0, -1),
			self::NorthEast => new Hex(1, -1),
		};
	}

	public function opposite(): self
	{
		return self::from(($this->value + 3) % 6);
	}

	public function rotate(int $sixths): self
	{
		return self::from((($this->value + $sixths) % 6 + 6) % 6);
	}
}
