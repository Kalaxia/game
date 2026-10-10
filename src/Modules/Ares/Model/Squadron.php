<?php

declare(strict_types=1);

namespace App\Modules\Ares\Model;

use App\Modules\Ares\Domain\Model\ShipCategory;
use Symfony\Component\Uid\Uuid;

/**
 * A squadron of a fleet: ships of a single category, on one cell of the formation grid (D9).
 *
 * The cell (q, r) is an axial offset from the center of the formation, the front facing east,
 * as in the battle engine (FormationSlot).
 */
class Squadron
{
	public function __construct(
		public Uuid $id,
		public Commander $commander,
		public ShipCategory $shipCategory,
		public int $ships,
		public int $q,
		public int $r,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $updatedAt,
	) {
	}

	public function isAt(int $q, int $r): bool
	{
		return $this->q === $q && $this->r === $r;
	}
}
