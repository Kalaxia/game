<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Election\DivineDesignation;
use App\Shared\Domain\Event\LoggerEvent;
use App\Shared\Domain\Event\MessengerEvent;
use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;
use Psr\Log\LoggerInterface;

readonly class NewTheocraticCampaignEvent implements LoggerEvent, MessengerEvent
{
	public function __construct(
		private DivineDesignation $divineDesignation,
	) {
	}

	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionName} has a new theocratic campaign.', [
			'factionName' => $this->divineDesignation->faction->identifier,
		]);
	}

	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage
	{
		return new BallotMessage($this->divineDesignation->faction->id);
	}

	public function getDelay(): ?\DateTimeImmutable
	{
		return $this->divineDesignation->endedAt;
	}
}
