<?php

namespace App\Modules\Artemis\Application\Handler;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\GetCommanderPev;
use App\Modules\Ares\Model\Commander;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Planet;

readonly class PlayerSpyingHandler extends SpyingHandler
{
	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private CommercialRouteRepositoryInterface $commercialRouteRepository,
		private GetCommanderPev $getCommanderPev,
	) {
	}

	protected function processSpyingMission(SpyReport $spyReport): void
	{
		$planet = $spyReport->place;

		$spyReport->resources = $planet->resourcesStorage;

		$spyReport->commercialRouteIncome = $this->commercialRouteRepository->getPlanetIncome($planet);

		$commandersArray = [];
		$commanders = $this->commanderRepository->getPlanetCommanders(
			$planet,
			[Commander::AFFECTED, Commander::MOVING, Commander::IN_BATTLE],
		);

		foreach ($commanders as $commander) {
			$commandersArray[] = [
				'name' => $commander->name,
				'avatar' => $commander->avatar,
				'level' => $commander->level,
				// Officer traits are revealed by spying (D20)
				'traits' => $commander->traits,
				'statement' => $commander->statement,
				'pev' => ($this->getCommanderPev)($commander),
				'army' => $commander->getNbrShipByType(),
			];
		}
		$spyReport->commanders = $commandersArray;
	}

	protected function getAntiSpyCoeff(Planet $place): int
	{
		return $place->antiSpyAverage;
	}
}
