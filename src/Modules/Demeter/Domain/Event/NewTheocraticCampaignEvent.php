<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Election\DivineDesignation;
use App\Shared\Domain\Event\MessengerEvent;
use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;

readonly class NewTheocraticCampaignEvent implements MessengerEvent
{
	public function __construct(
		private DivineDesignation $divineDesignation,
	) {
	}

	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage
	{
		return new BallotMessage($this->divineDesignation->faction->id);
	}

	public function getStamps(): array
	{
		return [DateTimeConverter::to_delay_stamp($this->divineDesignation->endedAt)];
	}
}
