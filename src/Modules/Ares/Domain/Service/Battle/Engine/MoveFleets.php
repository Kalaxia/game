<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use Random\Randomizer;

/**
 * Fleets that cannot reach any enemy squadron move toward the closest free front (Q10).
 *
 * A fleet is engaged when an enemy squadron stands within the range of its squadrons from the edge
 * of its area. Being next to an enemy fleet is not enough, its squadrons may all face another front.
 * Squadrons can move into other areas, but moving the whole area keeps the formation together
 * and brings slow heavy ships to the front.
 *
 * Otherwise, the fleet looks for the free area closest to the enemy squadrons, going around the other
 * fleets, and moves one area toward it each turn.
 */
readonly class MoveFleets
{
	/**
	 * @return list<BattleEvent>
	 */
	public function __invoke(BattleState $state, ?Randomizer $randomizer = null): array
	{
		$events = [];

		foreach ($state->getActiveFleets() as $fleet) {
			$enemySquadrons = $state->getEnemySquadrons($fleet->side);
			$reach = $this->getReach($fleet);

			if ([] === $enemySquadrons || null === $reach) {
				continue;
			}

			$currentDistance = $this->getDistanceToEnemies($state, $fleet->cluster, $enemySquadrons);

			if ($currentDistance <= $reach) {
				continue;
			}

			$step = $this->findNextStep($state, $fleet, $enemySquadrons, $currentDistance, $randomizer);

			if (null === $step || !$this->canMoveTo($state, $fleet, $step)) {
				continue;
			}

			$from = $fleet->cluster;
			[$translation, $squadrons] = $this->move($state, $fleet, $step);
			$events[] = BattleEvent::fleetMoved($fleet, $from, $translation, $squadrons);
		}

		return $events;
	}

	/**
	 * Longest range of the fleet's squadrons, null when it has none left.
	 */
	private function getReach(BattleFleet $fleet): ?int
	{
		$ranges = array_map(fn (BattleSquadron $squadron) => $squadron->type->range, $fleet->getActiveSquadrons());

		return [] === $ranges ? null : max($ranges);
	}

	/**
	 * Distance between the edge of an area and the closest enemy squadron.
	 *
	 * @param list<BattleSquadron> $enemySquadrons
	 */
	private function getDistanceToEnemies(BattleState $state, Hex $cluster, array $enemySquadrons): int
	{
		$center = $state->grid->clusterCenter($cluster);

		return max(0, min(array_map(
			fn (BattleSquadron $enemy) => $center->distanceTo($enemy->cell),
			$enemySquadrons,
		)) - $state->grid->clusterRadius);
	}

	/**
	 * Breadth-first search through the free areas, toward the one closest to the enemy squadrons.
	 *
	 * @param list<BattleSquadron> $enemySquadrons
	 */
	private function findNextStep(BattleState $state, BattleFleet $fleet, array $enemySquadrons, int $currentDistance, ?Randomizer $randomizer): ?Hex
	{
		$start = $fleet->cluster;
		$firstSteps = [$start->toKey() => null];
		$queue = [$start];
		$best = null;
		$bestDistance = $currentDistance;

		while ([] !== $queue) {
			$cluster = array_shift($queue);
			$neighbours = $cluster->neighbours();
			// Equally good paths are picked at random: a fixed order would favour one direction
			if (null !== $randomizer) {
				$neighbours = $randomizer->shuffleArray($neighbours);
			}

			foreach ($neighbours as $neighbour) {
				if (array_key_exists($neighbour->toKey(), $firstSteps) || !$this->isFreeArea($state, $neighbour)) {
					continue;
				}

				$firstSteps[$neighbour->toKey()] = $firstSteps[$cluster->toKey()] ?? $neighbour;
				$queue[] = $neighbour;

				$distance = $this->getDistanceToEnemies($state, $neighbour, $enemySquadrons);
				// Breadth-first order: the first area found at a given distance is also the closest to reach
				if ($distance < $bestDistance) {
					$best = $neighbour;
					$bestDistance = $distance;
				}
			}
		}

		return null !== $best ? $firstSteps[$best->toKey()] : null;
	}

	private function isFreeArea(BattleState $state, Hex $cluster): bool
	{
		return $state->isClusterOnBattlefield($cluster) && null === $state->getFleetOnCluster($cluster);
	}

	/**
	 * The squadrons moving with the fleet need free cells in the destination area.
	 */
	private function canMoveTo(BattleState $state, BattleFleet $fleet, Hex $destination): bool
	{
		$translation = $this->getTranslation($state, $fleet, $destination);

		foreach ($this->getSquadronsInArea($state, $fleet) as $squadron) {
			$occupant = $state->getSquadronAt($squadron->cell->add($translation));

			if (null !== $occupant && $occupant->getFleet() !== $fleet) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return array{Hex, list<BattleSquadron>} translation of the fleet area and squadrons moved with it
	 */
	private function move(BattleState $state, BattleFleet $fleet, Hex $destination): array
	{
		$translation = $this->getTranslation($state, $fleet, $destination);
		// Squadrons roaming outside the fleet area stay where they are
		$squadrons = $this->getSquadronsInArea($state, $fleet);

		foreach ($squadrons as $squadron) {
			$state->removeSquadron($squadron);
		}
		foreach ($squadrons as $squadron) {
			$state->placeSquadron($squadron, $squadron->cell->add($translation));
		}

		$fleet->cluster = $destination;

		return [$translation, $squadrons];
	}

	private function getTranslation(BattleState $state, BattleFleet $fleet, Hex $destination): Hex
	{
		return $state->grid->clusterCenter($destination)->subtract($state->grid->clusterCenter($fleet->cluster));
	}

	/**
	 * @return list<BattleSquadron>
	 */
	private function getSquadronsInArea(BattleState $state, BattleFleet $fleet): array
	{
		return array_values(array_filter(
			$fleet->getActiveSquadrons(),
			fn (BattleSquadron $squadron) => $state->grid->clusterOf($squadron->cell)->equals($fleet->cluster),
		));
	}
}
