<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Battle;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Zeus\Model\Player;

/**
 * Only the participants watch a battle live and see its report (Q17): the players whose officers
 * took part in it, and the owner of the planet.
 */
readonly class CanWatchBattle
{
	public function __invoke(Battle $battle, Player $player): bool
	{
		return $battle->hasPlayer($player);
	}
}
