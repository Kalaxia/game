<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Handler;

use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;

readonly class GetFleetSpeed
{
	public function __invoke(?PlayerBonus $playerBonus): int
	{
		$bonus = null !== $playerBonus
			? intval(round(Commander::FLEETSPEED * (3 * ($playerBonus->bonuses->get(PlayerBonusId::SHIP_SPEED) / 100))))
			: 0;

		return Commander::FLEETSPEED + $bonus;
	}
}
