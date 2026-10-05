<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;

/**
 * Fleets out of contact with the enemy move one cluster closer to the nearest enemy fleet,
 * if the cluster is free. Otherwise they wait (Q10).
 */
readonly class MoveFleets
{
	/**
	 * @return list<BattleEvent>
	 */
	public function __invoke(BattleState $state): array
	{
		$events = [];

		foreach ($state->getActiveFleets() as $fleet) {
			$enemyClusters = array_map(fn (BattleFleet $enemy) => $enemy->cluster, $state->getEnemyFleets($fleet->side));

			if ([] === $enemyClusters || 1 >= $this->getMinDistance($fleet->cluster, $enemyClusters)) {
				continue;
			}

			$destination = $this->findDestination($state, $fleet, $enemyClusters);

			if (null === $destination) {
				continue;
			}

			$from = $fleet->cluster;
			$this->move($state, $fleet, $destination);
			$events[] = BattleEvent::fleetMoved($fleet, $from);
		}

		return $events;
	}

	/**
	 * @param list<Hex> $enemyClusters
	 */
	private function findDestination(BattleState $state, BattleFleet $fleet, array $enemyClusters): ?Hex
	{
		$currentDistance = $this->getMinDistance($fleet->cluster, $enemyClusters);
		$best = null;
		$bestDistance = $currentDistance;

		foreach ($fleet->cluster->neighbours() as $cluster) {
			if (!$state->isClusterOnBattlefield($cluster) || null !== $state->getFleetOnCluster($cluster)) {
				continue;
			}

			// Squadrons roaming in this cluster block the way
			foreach ($state->grid->clusterCells($cluster) as $cell) {
				if (!$state->isCellFree($cell)) {
					continue 2;
				}
			}

			$distance = $this->getMinDistance($cluster, $enemyClusters);

			if ($distance < $bestDistance) {
				$best = $cluster;
				$bestDistance = $distance;
			}
		}

		return $best;
	}

	private function move(BattleState $state, BattleFleet $fleet, Hex $destination): void
	{
		$translation = $state->grid->clusterCenter($destination)->subtract($state->grid->clusterCenter($fleet->cluster));
		// Squadrons roaming outside the fleet area stay where they are
		$squadrons = array_filter(
			$fleet->getActiveSquadrons(),
			fn ($squadron) => $state->grid->clusterOf($squadron->cell)->equals($fleet->cluster),
		);

		foreach ($squadrons as $squadron) {
			$state->removeSquadron($squadron);
		}
		foreach ($squadrons as $squadron) {
			$squadron->cell = $squadron->cell->add($translation);
			$state->placeSquadron($squadron, $squadron->cell);
		}

		$fleet->cluster = $destination;
	}

	/**
	 * @param list<Hex> $clusters
	 */
	private function getMinDistance(Hex $cluster, array $clusters): int
	{
		return min(array_map(fn (Hex $other) => $cluster->distanceTo($other), $clusters));
	}
}
