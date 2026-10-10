<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Model\Battle\Grid\HexDirection;

/**
 * Brings arriving fleets onto the battlefield, in order of arrival (Q9, Q10).
 *
 * A fleet takes the free cluster closest to an enemy fleet, in contact if possible. Without enemies,
 * it gathers near its allies, or at the center of the battlefield. Its formation faces the closest enemy.
 *
 * Fleets arriving on the same turn are all placed first, then oriented and deployed, so that each one
 * faces the enemies arriving with it and keeps its formation as composed.
 */
readonly class DeployFleets
{
	/**
	 * @return list<BattleEvent>
	 */
	public function __invoke(BattleState $state): array
	{
		$events = [];
		/** @var list<array{BattleFleet, Hex}> $arrivals */
		$arrivals = [];

		foreach ($state->fleets as $fleet) {
			if ($fleet->isDeployed() || $fleet->arrivalTurn > $state->turn) {
				continue;
			}

			if (null === ($cluster = $this->findCluster($state, $fleet, $arrivals))) {
				// The battlefield is full: the fleet waits for a free cluster
				continue;
			}

			$arrivals[] = [$fleet, $cluster];
		}

		foreach ($arrivals as [$fleet, $cluster]) {
			$this->deploy($state, $fleet, $cluster, $arrivals);
			$events[] = BattleEvent::fleetJoined($fleet);
		}

		return [...$events, ...$this->turnIdleFleetsTowardEnemies($state)];
	}

	/**
	 * Fleets deployed before any enemy arrived turn their formation toward the first enemies.
	 *
	 * @return list<BattleEvent>
	 */
	private function turnIdleFleetsTowardEnemies(BattleState $state): array
	{
		$events = [];

		foreach ($state->getActiveFleets() as $fleet) {
			if ($fleet->hasFacedEnemy || [] === $state->getEnemyFleets($fleet->side)) {
				continue;
			}

			$orientation = $this->getOrientation($state, $fleet, array_map(fn (BattleFleet $enemy) => $enemy->cluster, $state->getEnemyFleets($fleet->side)));
			$rotation = $orientation->value - $fleet->orientation->value;
			$center = $state->grid->clusterCenter($fleet->cluster);
			$clusterCells = $state->grid->clusterCells($fleet->cluster);
			$squadrons = array_filter(
				$fleet->getActiveSquadrons(),
				fn (BattleSquadron $squadron) => $state->grid->clusterOf($squadron->cell)->equals($fleet->cluster),
			);
			$origins = [];

			foreach ($squadrons as $squadron) {
				$origins[$squadron->identifier] = $squadron->cell;
				$state->removeSquadron($squadron);
			}
			foreach ($squadrons as $squadron) {
				$cell = $squadron->cell->rotateAround($center, $rotation);
				if (!$state->isCellFree($cell)) {
					$cell = $this->findClosestFreeCell($state, $clusterCells, $cell) ?? $origins[$squadron->identifier];
				}
				$state->placeSquadron($squadron, $cell);

				if (!$cell->equals($origins[$squadron->identifier])) {
					$events[] = BattleEvent::squadronMoved($squadron, $origins[$squadron->identifier]);
				}
			}

			$fleet->orientation = $orientation;
			$fleet->hasFacedEnemy = true;
		}

		return $events;
	}

	/**
	 * @param list<array{BattleFleet, Hex}> $arrivals fleets already placed this turn
	 */
	private function findCluster(BattleState $state, BattleFleet $fleet, array $arrivals): ?Hex
	{
		$enemyClusters = $this->getEnemyClusters($state, $fleet, $arrivals);
		$allyClusters = [
			...array_map(
				fn (BattleFleet $ally) => $ally->cluster,
				array_values(array_filter($state->getActiveFleets(), fn (BattleFleet $ally) => $ally->side === $fleet->side)),
			),
			...array_map(fn (array $arrival) => $arrival[1], array_values(array_filter($arrivals, fn (array $arrival) => $arrival[0]->side === $fleet->side))),
		];
		$takenClusters = array_map(fn (array $arrival) => $arrival[1]->toKey(), $arrivals);
		$center = $state->getBattlefieldCenter();
		$best = null;
		$bestScore = null;

		for ($q = 0; $q < $state->battlefieldSize; ++$q) {
			for ($r = 0; $r < $state->battlefieldSize; ++$r) {
				$cluster = new Hex($q, $r);

				if (null !== $state->getFleetOnCluster($cluster) || in_array($cluster->toKey(), $takenClusters, true)) {
					continue;
				}

				$score = [
					$this->getMinDistance($cluster, $enemyClusters),
					$this->getMinDistance($cluster, $allyClusters),
					$cluster->distanceTo($center),
				];

				if (null === $bestScore || $score < $bestScore) {
					$best = $cluster;
					$bestScore = $score;
				}
			}
		}

		return $best;
	}

	/**
	 * @param list<array{BattleFleet, Hex}> $arrivals fleets arriving this turn
	 */
	private function deploy(BattleState $state, BattleFleet $fleet, Hex $cluster, array $arrivals): void
	{
		$enemyClusters = $this->getEnemyClusters($state, $fleet, $arrivals);
		$fleet->cluster = $cluster;
		$fleet->orientation = $this->getOrientation($state, $fleet, $enemyClusters);
		$fleet->hasFacedEnemy = [] !== $enemyClusters;
		$center = $state->grid->clusterCenter($cluster);
		$clusterCells = $state->grid->clusterCells($cluster);

		foreach ($fleet->formation as $index => $slot) {
			$cell = $center->add($slot->offset->rotate($fleet->orientation->value));

			if (!$state->isCellFree($cell)) {
				$cell = $this->findClosestFreeCell($state, $clusterCells, $cell)
					?? throw new \LogicException(sprintf('No room left to deploy fleet %s', $fleet->identifier));
			}

			$squadron = new BattleSquadron(
				identifier: sprintf('%s-%d', $fleet->identifier, $index),
				type: $slot->type,
				ships: $slot->ships,
				cell: $cell,
				fleet: $fleet,
				maxShipLife: (int) round($slot->type->life * $state->durabilityFactor),
			);
			$fleet->squadrons[] = $squadron;
			$state->placeSquadron($squadron, $cell);
		}
	}

	/**
	 * Fleets and arrivals of the other sides.
	 *
	 * @param list<array{BattleFleet, Hex}> $arrivals
	 *
	 * @return list<Hex>
	 */
	private function getEnemyClusters(BattleState $state, BattleFleet $fleet, array $arrivals): array
	{
		return [
			...array_map(fn (BattleFleet $enemy) => $enemy->cluster, $state->getEnemyFleets($fleet->side)),
			...array_map(fn (array $arrival) => $arrival[1], array_values(array_filter($arrivals, fn (array $arrival) => $arrival[0]->side !== $fleet->side))),
		];
	}

	/**
	 * @param list<Hex> $enemyClusters
	 */
	private function getOrientation(BattleState $state, BattleFleet $fleet, array $enemyClusters): HexDirection
	{
		$center = $state->grid->clusterCenter($fleet->cluster);
		$targets = array_map(fn (Hex $cluster) => $state->grid->clusterCenter($cluster), $enemyClusters);

		if ([] === $targets) {
			$targets = [$state->grid->clusterCenter($state->getBattlefieldCenter())];
		}

		usort($targets, fn (Hex $a, Hex $b) => $center->distanceTo($a) <=> $center->distanceTo($b));
		$target = $targets[0];
		$distance = $center->distanceTo($target);

		if (0 === $distance) {
			return HexDirection::East;
		}

		$directions = HexDirection::cases();
		usort($directions, fn (HexDirection $a, HexDirection $b) => $center->add($a->toHex()->scale($distance))->distanceTo($target)
			<=> $center->add($b->toHex()->scale($distance))->distanceTo($target));

		return $directions[0];
	}

	/**
	 * @param list<Hex> $cells
	 */
	private function findClosestFreeCell(BattleState $state, array $cells, Hex $target): ?Hex
	{
		$freeCells = array_values(array_filter($cells, fn (Hex $cell) => $state->isCellFree($cell)));
		usort($freeCells, fn (Hex $a, Hex $b) => $a->distanceTo($target) <=> $b->distanceTo($target));

		return $freeCells[0] ?? null;
	}

	/**
	 * @param list<Hex> $clusters
	 */
	private function getMinDistance(Hex $cluster, array $clusters): int
	{
		return [] === $clusters ? 0 : min(array_map(fn (Hex $other) => $cluster->distanceTo($other), $clusters));
	}
}
