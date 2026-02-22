<?php

namespace App\Modules\Zeus\Application\Handler\Bonus;

use App\Modules\Zeus\Model\PlayerBonus;

interface BonusApplierInterface
{
	/**
	 * Applies the given player (current if not set) bonus on a value
	 * This returns the modifier value without adding the initial value to allow to know what the modifier gives as bonus/malus.
	 */
	public function apply(int|float $initialValue, int $modifierId, ?PlayerBonus $playerBonus = null): float;
}
