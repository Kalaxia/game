<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Message;

use App\Shared\Domain\Message\AsyncMessage;
use App\Shared\Domain\Message\UniqueMessage;
use Symfony\Component\Uid\Uuid;

readonly class SenateUpdateMessage implements AsyncMessage, UniqueMessage
{
	public function __construct(public Uuid $factionId)
	{
	}

	public function getUniqueId(): string
	{
		return sprintf('senate_update.%s', $this->factionId->toRfc4122());
	}
}
