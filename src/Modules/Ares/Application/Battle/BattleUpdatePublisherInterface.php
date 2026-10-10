<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;

/**
 * Pushes each resolved turn of a running battle to the players allowed to watch it (Q17).
 */
interface BattleUpdatePublisherInterface
{
	public function publishTurn(Battle $battle, BattleState $state, TurnResult $turn): void;
}
