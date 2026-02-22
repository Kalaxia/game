<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Election\Putsch;
use App\Shared\Domain\Event\LoggerEvent;
use App\Shared\Domain\Event\MessengerEvent;
use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;
use Psr\Log\LoggerInterface;

readonly class NewPutschAttemptEvent implements LoggerEvent, MessengerEvent
{
	public function __construct(
		private Putsch $putsch,
	) {
	}

	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionName} has a new putsch attempt.', [
			'factionName' => $this->putsch->faction->identifier,
		]);
	}

	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage
	{
		return new BallotMessage($this->putsch->faction->id);
	}

	public function getDelay(): ?\DateTimeImmutable
	{
		return $this->putsch->endedAt;
	}
}
