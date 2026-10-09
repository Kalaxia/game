<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\Battle\BuildFleetFormation;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Resource\SquadronResource;

/**
 * Fleet defending a planet without owner (D16), with the legacy formula: the officer level grows
 * with the maximum danger and the population of the planet, the number of squadrons with its
 * current danger, and their ships are picked in SquadronResource. The ships are then arranged
 * in a formation by the officer (BuildFleetFormation, through CreateBattle).
 */
readonly class GenerateRebelFleet
{
	public const string IDENTIFIER_PREFIX = 'rebels-';

	public function __construct(
		private BattleShipTypeRegistry $battleShipTypeRegistry,
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		private BuildFleetFormation $buildFleetFormation,
	) {
	}

	/**
	 * @return array<int, int> ships by ship category value, without the categories it does not have
	 */
	public function getShips(Planet $planet): array
	{
		$level = $this->getLevel($planet);
		$squadronsCount = intval(ceil($level * (($planet->danger + 1) / ($planet->maxDanger + 1))));
		$ships = [];

		for ($squadron = 0; $squadron < $squadronsCount; ++$squadron) {
			foreach ($this->getSquadronShips($planet, $level, $squadron) as $category => $quantity) {
				if ($quantity > 0) {
					$ships[$category] = ($ships[$category] ?? 0) + $quantity;
				}
			}
		}

		ksort($ships);

		return $this->fitInFormation($ships);
	}

	public function getPev(Planet $planet): int
	{
		$pev = 0;

		foreach ($this->getShips($planet) as $category => $quantity) {
			$pev += ($this->getShipCategoriesConfiguration)($category, 'pev') * $quantity;
		}

		return $pev;
	}

	public function getLevel(Planet $planet): int
	{
		return intval(ceil((
			(($planet->maxDanger / (Planet::DANGERMAX / Planet::LEVELMAXVCOMMANDER)) * 9)
			+ ($planet->population / (Planet::POPMAX / Planet::LEVELMAXVCOMMANDER))
		) / 10));
	}

	/**
	 * Description of the rebel fleet for CreateBattle and JoinBattle.
	 *
	 * @return array{identifier: string, name: string, side: int, faction: int, officer_level: int, ships: array<string, int>}
	 */
	public function describe(Planet $planet, int $side): array
	{
		$ships = [];
		foreach ($this->getShips($planet) as $category => $quantity) {
			$ships[$this->battleShipTypeRegistry->getByCategory(ShipCategory::from($category))->identifier] = $quantity;
		}

		return [
			'identifier' => self::IDENTIFIER_PREFIX.$planet->id->toRfc4122(),
			'name' => 'Rebelles',
			'side' => $side,
			'faction' => ColorResource::NO_FACTION,
			'officer_level' => $this->getLevel($planet),
			'ships' => $ships,
		];
	}

	/**
	 * The strongest rebel planets would need more squadrons than a formation holds:
	 * the type filling the most squadrons loses one squadron until the fleet fits.
	 *
	 * @param array<int, int> $ships
	 *
	 * @return array<int, int>
	 */
	private function fitInFormation(array $ships): array
	{
		$sizes = [];
		foreach (array_keys($ships) as $category) {
			$sizes[$category] = $this->battleShipTypeRegistry->getByCategory(ShipCategory::from($category))->squadronSize;
		}
		$squadronsOf = fn (int $category) => intval(ceil($ships[$category] / $sizes[$category]));

		while (array_sum(array_map($squadronsOf, array_keys($ships))) > $this->buildFleetFormation->getCapacity()) {
			$largest = array_reduce(array_keys($ships), fn (?int $carry, int $category) => null === $carry || $squadronsOf($category) > $squadronsOf($carry) ? $category : $carry);
			$ships[$largest] = max(0, $ships[$largest] - $sizes[$largest]);

			if (0 === $ships[$largest]) {
				unset($ships[$largest]);
			}
		}

		return $ships;
	}

	/**
	 * A composition of SquadronResource suited to the officer level, picked from the planet coefficients.
	 *
	 * @return list<int>
	 */
	private function getSquadronShips(Planet $planet, int $level, int $squadron): array
	{
		$compositions = SquadronResource::$squadrons;
		$compositionsCount = count($compositions);
		$index = max(0, ($planet->coefHistory * $planet->coefResources * $planet->position * $squadron) % $compositionsCount);

		for ($i = 0; $i < $compositionsCount; ++$i, $index = ($index + 1) % $compositionsCount) {
			if ($compositions[$index][0] <= $level && $compositions[$index][1] >= $level) {
				return $compositions[$index][2];
			}
		}

		return $compositions[0][2];
	}
}
