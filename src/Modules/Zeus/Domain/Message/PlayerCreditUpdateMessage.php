<?php

namespace App\Modules\Zeus\Domain\Message;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;

readonly class PlayerCreditUpdateMessage implements AsyncHighPriorityMessage
{
	public function __construct(private int $playerId)
	{
	}

	public function getPlayerId(): int
	{
		return $this->playerId;
	}
}
