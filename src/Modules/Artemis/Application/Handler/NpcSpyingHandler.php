<?php

namespace App\Modules\Artemis\Application\Handler;

use App\Modules\Ares\Application\Battle\GenerateRebelFleet;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Model\Commander;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Galaxy\Domain\Entity\Planet;

readonly class NpcSpyingHandler extends SpyingHandler
{
	public function __construct(
		private GenerateRebelFleet $generateRebelFleet,
	) {
	}

	protected function processSpyingMission(SpyReport $spyReport): void
	{
		$place = $spyReport->place;

		$spyReport->resources = $place->resourcesStorage;

		// The rebel fleet that would defend the planet (D16)
		$spyReport->commanders = [[
			'name' => 'rebelle',
			'avatar' => 't3-c4',
			'level' => $this->generateRebelFleet->getLevel($place),
			'traits' => [],
			'statement' => Commander::AFFECTED,
			'pev' => $this->generateRebelFleet->getPev($place),
			'army' => array_replace(array_fill(0, count(ShipCategory::cases()), 0), $this->generateRebelFleet->getShips($place)),
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
