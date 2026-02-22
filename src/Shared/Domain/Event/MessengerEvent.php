<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;

interface MessengerEvent
{
	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage;

	public function getDelay(): ?\DateTimeImmutable;
}
