<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Model\Election\DemocraticElection;
use App\Shared\Domain\Event\MessengerEvent;
use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;

readonly class NewDemocraticCampaignEvent implements MessengerEvent
{
	public function __construct(
		private DemocraticElection $democraticElection,
	) {
	}

	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage
	{
		return new ElectionMessage($this->democraticElection->faction->id);
	}

	public function getStamps(): array
	{
		return [DateTimeConverter::to_delay_stamp($this->democraticElection->campaignEndedAt)];
	}
}
