<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\Grid\ClusteredHexGrid;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Model\Battle\Rules\BattleRules;

/**
 * Complete state of a battle between two turns.
 *
 * The battlefield is a parallelogram of size × size clusters, each cluster being the area of one fleet.
 * Sides are mutually hostile: see ResolveBattleSides.
 */
class BattleState
{
	public int $turn = 0;
	public bool $isOver = false;
	public ?int $winningSide = null;
	/** Turn from which fleets fire, null while the engagement has not started (D11) */
	public ?int $engagedAtTurn = null;
	/** Turn from which the engagement has been delayed, null when it was not */
	public ?int $engagementDelayedAtTurn = null;
	/** @var array<string, BattleSquadron> squadrons by cell key */
	private array $occupancy = [];

	/**
	 * @param list<BattleFleet> $fleets every fleet of the battle, deployed or not, in order of arrival
	 */
	public function __construct(
		public readonly ClusteredHexGrid $grid,
		public readonly int $battlefieldSize,
		public readonly int $seed,
		public array $fleets = [],
		public readonly int $maxTurns = 500,
		/** Multiplies the life of every ship, to tune the duration of battles (D1) */
		public readonly float $durabilityFactor = 1.0,
		/** Number of squadron actions (fire) per turn, shared among the fleets, null for no limit (R9) */
		public readonly ?int $maxActionsPerTurn = null,
		public readonly BattleRules $rules = new BattleRules(),
	) {
	}

	public function addFleet(BattleFleet $fleet): void
	{
		$this->fleets[] = $fleet;
	}

	public function isClusterOnBattlefield(Hex $cluster): bool
	{
		return $cluster->q >= 0 && $cluster->q < $this->battlefieldSize
			&& $cluster->r >= 0 && $cluster->r < $this->battlefieldSize;
	}

	public function isCellOnBattlefield(Hex $cell): bool
	{
		return $this->isClusterOnBattlefield($this->grid->clusterOf($cell));
	}

	public function getBattlefieldCenter(): Hex
	{
		$middle = intdiv($this->battlefieldSize, 2);

		return new Hex($middle, $middle);
	}

	/**
	 * Fleet standing on a cluster. Destroyed fleets free their cluster.
	 */
	public function getFleetOnCluster(Hex $cluster): ?BattleFleet
	{
		foreach ($this->getActiveFleets() as $fleet) {
			if ($fleet->cluster->equals($cluster)) {
				return $fleet;
			}
		}

		return null;
	}

	public function getSquadronAt(Hex $cell): ?BattleSquadron
	{
		return $this->occupancy[$cell->toKey()] ?? null;
	}

	public function isCellFree(Hex $cell): bool
	{
		return null === $this->getSquadronAt($cell);
	}

	public function placeSquadron(BattleSquadron $squadron, Hex $cell): void
	{
		if (!$this->isCellFree($cell) && $this->getSquadronAt($cell) !== $squadron) {
			throw new \LogicException(sprintf('Cell %s is already occupied', $cell->toKey()));
		}

		unset($this->occupancy[$squadron->cell->toKey()]);
		$squadron->cell = $cell;
		$this->occupancy[$cell->toKey()] = $squadron;
	}

	public function removeSquadron(BattleSquadron $squadron): void
	{
		if ($this->getSquadronAt($squadron->cell) === $squadron) {
			unset($this->occupancy[$squadron->cell->toKey()]);
		}
	}

	/**
	 * @return list<BattleFleet>
	 */
	public function getDeployedFleets(): array
	{
		return array_values(array_filter($this->fleets, fn (BattleFleet $fleet) => $fleet->isDeployed()));
	}

	/**
	 * @return list<BattleFleet>
	 */
	public function getActiveFleets(): array
	{
		return array_values(array_filter($this->getDeployedFleets(), fn (BattleFleet $fleet) => !$fleet->isDestroyed() && !$fleet->hasFled()));
	}

	/**
	 * @return list<BattleSquadron>
	 */
	public function getActiveSquadrons(): array
	{
		return array_merge([], ...array_map(fn (BattleFleet $fleet) => $fleet->getActiveSquadrons(), $this->getActiveFleets()));
	}

	/**
	 * @return list<BattleSquadron>
	 */
	public function getEnemySquadrons(int $side): array
	{
		return array_values(array_filter(
			$this->getActiveSquadrons(),
			fn (BattleSquadron $squadron) => $squadron->getFleet()->side !== $side,
		));
	}

	/**
	 * @return list<BattleFleet>
	 */
	public function getEnemyFleets(int $side): array
	{
		return array_values(array_filter($this->getActiveFleets(), fn (BattleFleet $fleet) => $fleet->side !== $side));
	}

	/**
	 * Sides still able to fight: with active fleets, or with fleets still on their way.
	 *
	 * @return list<int>
	 */
	public function getRemainingSides(): array
	{
		$sides = [];

		foreach ($this->fleets as $fleet) {
			if (!$fleet->isDeployed() || (!$fleet->isDestroyed() && !$fleet->hasFled())) {
				$sides[$fleet->side] = true;
			}
		}

		return array_keys($sides);
	}
}
