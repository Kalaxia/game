<?php

namespace App\Modules\Artemis\Application\Handler;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Application\Handler\VirtualCommanderHandler;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Galaxy\Domain\Entity\Planet;

readonly class NpcSpyingHandler extends SpyingHandler
{
	public function __construct(
		private VirtualCommanderHandler $virtualCommanderHandler,
		private CommanderArmyHandler $commanderArmyHandler,
	) {

	}

	protected function processSpyingMission(SpyReport $spyReport): void
	{
		$place = $spyReport->place;

		$spyReport->resources = $place->resourcesStorage;

		// generate a commander for the place
		$commander = $this->virtualCommanderHandler->createVirtualCommander($place);

		$spyReport->commanders = [[
			'name' => $commander->name,
			'avatar' => $commander->avatar,
			'level' => $commander->level,
			'line' => $commander->line,
			'statement' => $commander->statement,
			'pev' => $this->commanderArmyHandler->getPev($commander),
			'army' => $commander->getNbrShipByType(),
		]];
	}

	protected function getAntiSpyCoeff(Planet $place): int
	{
		return $place->maxDanger * 40;
	}

	#[\Override]
    protected function getTypeOfSpy(int $success, int $antiSpy): int
	{
		return SpyReport::TYP_NOT_CAUGHT;
	}
}
