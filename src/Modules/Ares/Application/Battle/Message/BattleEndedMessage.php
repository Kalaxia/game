<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle\Message;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use Symfony\Component\Uid\Uuid;

/**
 * A battle has resolved its last turn: its outcome is applied to the fleets and planets of the game.
 */
readonly class BattleEndedMessage implements AsyncHighPriorityMessage
{
	public function __construct(
		public Uuid $battleId,
	) {
	}
}
