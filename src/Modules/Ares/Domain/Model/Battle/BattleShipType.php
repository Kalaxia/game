<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\ShipCategory;

/**
 * Combat profile of a ship type in the new battle system.
 *
 * It is separate from ShipCategory while the new system only runs in the combat simulator:
 * new types (such as the bomber) have no ShipCategory yet.
 */
final readonly class BattleShipType
{
	/**
	 * @param list<int>       $attack           damage of each shot fired per round
	 * @param list<ShipClass> $preferredTargets ship classes targeted first, in order
	 */
	public function __construct(
		public string $identifier,
		public ?ShipCategory $shipCategory,
		public ShipClass $class,
		public ShipRole $role,
		public int $pev,
		public int $life,
		public int $defense,
		public int $speed,
		public array $attack,
		public int $squadronSize,
		public int $movement,
		public int $initiative,
		public int $range,
		public array $preferredTargets,
	) {
	}
}
