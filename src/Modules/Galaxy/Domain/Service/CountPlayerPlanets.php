<?php

namespace App\Modules\Galaxy\Domain\Service;

use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Model\Player;

readonly class CountPlayerPlanets
{
	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private PlanetRepositoryInterface $planetRepository,
	) {
	}

	public function __invoke(Player $player): int
	{
		$playerBases = $this->planetRepository->countPlayerPlanets($player);

		$conquerringCommanders = count(array_filter(
			$this->commanderRepository->getPlayerCommanders($player, [Commander::MOVING]),
			fn (Commander $commander) => CommanderMission::Colo === $commander->travelType,
		));

		return $playerBases + $conquerringCommanders;
	}
}
