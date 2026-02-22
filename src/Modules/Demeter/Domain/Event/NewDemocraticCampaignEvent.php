<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Model\Election\DemocraticElection;
use App\Shared\Domain\Event\LoggerEvent;
use App\Shared\Domain\Event\MessengerEvent;
use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;
use Psr\Log\LoggerInterface;

readonly class NewDemocraticCampaignEvent implements LoggerEvent, MessengerEvent
{
	public function __construct(
		private DemocraticElection $democraticElection,
	) {
	}

	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionName} has a new democratic campaign.', [
			'factionName' => $this->democraticElection->faction->identifier,
		]);
	}

	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage
	{
		return new ElectionMessage($this->democraticElection->faction->id);
	}

	public function getDelay(): ?\DateTimeImmutable
	{
		return $this->democraticElection->campaignEndedAt;
	}
}
