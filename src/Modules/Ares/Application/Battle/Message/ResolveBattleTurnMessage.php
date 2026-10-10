<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle\Message;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use Symfony\Component\Uid\Uuid;

/**
 * Resolves one turn of a running battle. The turn number makes the message idempotent:
 * a turn already resolved is ignored.
 */
readonly class ResolveBattleTurnMessage implements AsyncHighPriorityMessage
{
	public function __construct(
		public Uuid $battleId,
		public int $turn,
	) {
	}
}
