<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Election\Putsch;
use App\Shared\Domain\Event\MessengerEvent;
use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;

readonly class NewPutschAttemptEvent implements MessengerEvent
{
	public function __construct(
		private Putsch $putsch,
	) {
	}

	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage
	{
		return new BallotMessage($this->putsch->faction->id);
	}

	public function getStamps(): array
	{
		return [DateTimeConverter::to_delay_stamp($this->putsch->endedAt)];
	}
}
