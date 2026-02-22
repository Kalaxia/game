<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Psr\Log\LoggerInterface;

interface LoggerEvent
{
	public function log(LoggerInterface $logger): void;
}
