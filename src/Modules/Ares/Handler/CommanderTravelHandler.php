<?php

declare(strict_types=1);

namespace App\Modules\Ares\Handler;

use App\Modules\Ares\Application\Mission\ArriveAtPlanet;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Message\CommanderTravelMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CommanderTravelHandler
{
	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private ArriveAtPlanet $arriveAtPlanet,
	) {
	}

	public function __invoke(CommanderTravelMessage $commanderTravelMessage): void
	{
		$commander = $this->commanderRepository->get($commanderTravelMessage->getCommanderId());

		// The travel may have been cancelled or replaced meanwhile (only the latest arrival counts)
		if (null === $commander || !$commander->isMoving() || $commander->getArrivalDate() > new \DateTimeImmutable('+1 second')) {
			return;
		}

		($this->arriveAtPlanet)($commander);
	}
}
