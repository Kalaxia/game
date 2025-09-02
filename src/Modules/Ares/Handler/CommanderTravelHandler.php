<?php

declare(strict_types=1);

namespace App\Modules\Ares\Handler;

use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Manager\ConquestManager;
use App\Modules\Ares\Manager\LootManager;
use App\Modules\Ares\Message\CommanderTravelMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Manager\PlanetManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CommanderTravelHandler
{
	public function __construct(
		private CommanderManager             $commanderManager,
		private CommanderRepositoryInterface $commanderRepository,
		private ConquestManager              $conquestManager,
		private LootManager                  $lootManager,
		private PlaceManager                 $placeManager,
		private PlanetManager                $planetManager,
	) {
	}

	public function __invoke(CommanderTravelMessage $commanderTravelMessage): void
	{
		if (null === ($commander = $this->commanderRepository->get($commanderTravelMessage->getCommanderId()))) {
			return;
		}

		match ($commander->travelType) {
			CommanderMission::Move => $this->commanderManager->uChangeBase($commander),
			CommanderMission::Loot => $this->lootManager->loot($commander),
			CommanderMission::Colo => $this->conquestManager->conquer($commander),
			CommanderMission::Back => $this->moveBack($commander),
			default => throw new \LogicException(sprintf(
				'Commander %s has arrived without mission',
				$commander->id->toRfc4122(),
			)),
		};
	}

	protected function moveBack(Commander $commander): void
	{
		$this->placeManager->sendNotif($commander->destinationPlace, Planet::COMEBACK, $commander);

		$this->commanderManager->endTravel($commander, Commander::AFFECTED);

		if ($commander->resources > 0) {
			$this->planetManager->increaseResources($commander->base, $commander->resources);
			$commander->resources = 0;
		}
		$this->commanderRepository->save($commander);
	}
}
