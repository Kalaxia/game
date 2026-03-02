<?php

declare(strict_types=1);

namespace App\Shared\Domain\Message;

interface UniqueMessage
{
	/**
	 * Returns a unique identifier for the message.
	 *
	 * This identifier is used to prevent duplicate messages from being processed.
	 *
	 * @return string a unique identifier for the message
	 */
	public function getUniqueId(): string;
}
