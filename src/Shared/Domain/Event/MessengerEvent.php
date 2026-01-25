<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Messenger\Stamp\StampInterface;

interface MessengerEvent
{
	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage;

	/**
	 * @return list<StampInterface>
	 */
	public function getStamps(): array;
}
