<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;

/**
 * A squadron in a fleet formation, positioned relative to the formation center with the front facing east.
 */
final readonly class FormationSlot
{
	public function __construct(
		public BattleShipType $type,
		public int $ships,
		public Hex $offset,
	) {
	}
}
