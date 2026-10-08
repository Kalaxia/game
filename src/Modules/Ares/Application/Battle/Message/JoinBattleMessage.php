<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle\Message;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use Symfony\Component\Uid\Uuid;

/**
 * A fleet reaches a running battle, usually sent with a delay (e.g. the end of its travel).
 * The fleet is described as for CreateBattle.
 */
readonly class JoinBattleMessage implements AsyncHighPriorityMessage
{
	/**
	 * @param array<string, mixed> $fleet
	 */
	public function __construct(
		public Uuid $battleId,
		public array $fleet,
	) {
	}
}
