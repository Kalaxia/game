<?php

namespace App\Modules\Ares\Application\Handler;

use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Resource\SquadronResource;
use Symfony\Component\Uid\Uuid;

readonly class VirtualCommanderHandler
{
	public function __construct(
		private CommanderArmyHandler $commanderArmyHandler,
	) {

	}

	public function createVirtualCommander(Planet $place): Commander
	{
		$vCommander = new Commander(
			id: Uuid::v4(),
			name: 'rebelle',
			avatar: 't3-c4',
			player: null,
			base: null,
			enlistedAt: new \DateTimeImmutable(),
			sexe: 1,
			age: 42,
			level: $this->getVirtualCommanderLevel($place),
			statement: Commander::AFFECTED,
		);

		$squadronsCount = ceil($vCommander->level * (($place->danger + 1) / ($place->maxDanger + 1)));

		for ($squadronId = 0; $squadronId < $squadronsCount; ++$squadronId) {
			$vCommander->army[$squadronId] = $this->generateSquadron(
				$vCommander,
				$this->getVirtualCommanderSquadronShips($vCommander, $place, $squadronId),
			);
			$vCommander->squadronsIds[] = $squadronId;

			$this->commanderArmyHandler->initializeShips($vCommander->army[$squadronId]);
		}

		for ($squadronId = $vCommander->level - 1; $squadronId >= $squadronsCount; --$squadronId) {
			$vCommander->army[$squadronId] = $this->generateSquadron(
				$vCommander,
				[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
			);
			$vCommander->squadronsIds[] = $squadronId;
		}
		$vCommander->hasArmySetted = true;

		return $vCommander;
	}

	private function getVirtualCommanderLevel(Planet $place): int
	{
		return intval(ceil((
			(($place->maxDanger / (Planet::DANGERMAX / Planet::LEVELMAXVCOMMANDER)) * 9)
			+ ($place->population / (Planet::POPMAX / Planet::LEVELMAXVCOMMANDER))
		) / 10));
	}

	/**
	 * @return list<int>
	 */
	private function getVirtualCommanderSquadronShips(Commander $commander, Planet $place, int $squadronId): array
	{
		$level = $commander->level;
		$compositionsCount = count(SquadronResource::$squadrons);

		// TODO extract that part to a tested method
		$randomNumber = ($place->coefHistory * $place->coefResources * $place->position * $squadronId) % $compositionsCount;

		while ($randomNumber >= $compositionsCount) {
			$randomNumber -= $compositionsCount;
		}
		if ($randomNumber < 0) {
			$randomNumber = 0;
		}

		$ships = SquadronResource::$squadrons[0][2];

		for ($i = 0; $i < $compositionsCount; $i++, $randomNumber++) {
			if ($randomNumber >= $compositionsCount) {
				$randomNumber = 0;
			}

			if (SquadronResource::$squadrons[$randomNumber][0] <= $level && SquadronResource::$squadrons[$randomNumber][1] >= $level) {
				$ships = SquadronResource::$squadrons[$randomNumber][2];
				break;
			}
		}

		return $ships;
	}

	private function generateSquadron(Commander $commander, array $ships): Squadron
	{
		$squadron = new Squadron(
			id: Uuid::v4(),
			commander: $commander,
			createdAt: new \DateTimeImmutable(),
			updatedAt: new \DateTimeImmutable(),
		);

		$squadron->setShips($ships);

		return $squadron;
	}
}
