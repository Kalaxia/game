<?php

namespace App\Modules\Artemis\Application\Handler;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Gaia\Model\Place;

readonly class PlayerSpyingHandler extends SpyingHandler
{
	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private CommercialRouteRepositoryInterface $commercialRouteRepository,
		private CommanderArmyHandler $commanderArmyHandler,
	) {
	}

	protected function processSpyingMission(SpyReport $spyReport): void
	{
		$orbitalBase = $spyReport->place->base;

		$spyReport->resources = $orbitalBase->resourcesStorage;

		$spyReport->commercialRouteIncome = $this->commercialRouteRepository->getBaseIncome($orbitalBase);

		$commandersArray = [];
		$commanders = $this->commanderRepository->getBaseCommanders(
			$orbitalBase,
			[Commander::AFFECTED, Commander::MOVING],
		);

		foreach ($commanders as $commander) {
			$commandersArray[] = [
				'name' => $commander->name,
				'avatar' => $commander->avatar,
				'level' => $commander->level,
				'line' => $commander->line,
				'statement' => $commander->statement,
				'pev' => $this->commanderArmyHandler->getPev($commander),
				'army' => $commander->getNbrShipByType(),
			];
		}
		$spyReport->commanders = $commandersArray;
	}

	protected function getAntiSpyCoeff(Place $place): int
	{
		return $place->base->antiSpyAverage;
	}
}
