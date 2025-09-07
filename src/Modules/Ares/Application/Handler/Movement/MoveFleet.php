<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Handler\Movement;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Message\CommanderTravelMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Travel\Domain\Service\GetTravelDuration;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class MoveFleet
{
	public function __construct(
		private GetTravelDuration $getTravelDuration,
		private MessageBusInterface $messageBus,
	) {
	}

	public function __invoke(Commander $commander, Place $origin, Place $destination, CommanderMission $mission): void
	{
		$commander->destinationPlace = $destination;
		$commander->startPlace = $origin;
		$commander->travelType = $mission;
		$commander->statement = Commander::MOVING;

		$commander->departedAt = (CommanderMission::Back !== $mission)
			? new \DateTimeImmutable()
			: $commander->getArrivalDate();
		$commander->arrivedAt = ($this->getTravelDuration)(
			origin: $origin,
			destination: $destination,
			departureDate: $commander->departedAt,
			player: $commander->player,
		);

		$this->messageBus->dispatch(
			new CommanderTravelMessage($commander->id),
			[DateTimeConverter::to_delay_stamp($commander->getArrivalDate())],
		);
	}
}
