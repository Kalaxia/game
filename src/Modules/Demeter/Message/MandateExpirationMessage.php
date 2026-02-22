<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Message;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\UniqueMessage;
use Symfony\Component\Uid\Uuid;

class MandateExpirationMessage implements AsyncHighPriorityMessage, UniqueMessage
{
	public function __construct(
		public Uuid $factionId,
	) {
	}

	public function getUniqueId(): string
	{
		return sprintf('mandate_expiration.%s', $this->factionId->toRfc4122());
	}
}
