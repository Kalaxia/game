<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use Random\Randomizer;

/**
 * Squadrons with no enemy in range move toward a firing position, in initiative order (R4).
 *
 * A squadron explores the cells it may enter and heads, along the shortest path and up to its movement,
 * for the closest cell from which it can fire on its preferred target, or else on any enemy.
 * When no such cell is reachable, it gets as close as possible to its target.
 * The first squadron reaching a free cell holds it. Every squadron may enter the area of another fleet,
 * for instance to strike the rear of an enemy fleet already engaged (D4, amended).
 */
readonly class MoveSquadrons
{
	public function __construct(
		private SelectTarget $selectTarget = new SelectTarget(),
	) {
	}

	/**
	 * @param list<BattleSquadron> $squadrons in initiative order
	 *
	 * @return list<BattleEvent>
	 */
	public function __invoke(BattleState $state, array $squadrons, Randomizer $randomizer): array
	{
		$events = [];

		foreach ($squadrons as $squadron) {
			if ($squadron->isDestroyed() || null !== ($this->selectTarget)($state, $squadron, true)) {
				continue;
			}

			if (null === ($target = ($this->selectTarget)($state, $squadron, false))) {
				continue;
			}

			$path = $this->findPath($state, $squadron, $target, $randomizer);

			if ([] === $path) {
				continue;
			}

			$from = $squadron->cell;
			foreach (array_slice($path, 0, $squadron->type->movement) as $cell) {
				$state->placeSquadron($squadron, $cell);
			}

			$events[] = BattleEvent::squadronMoved($squadron, $from);
		}

		return $events;
	}

	/**
	 * Explores the reachable cells breadth first and picks the best destination:
	 * in range of the target, else in range of any enemy, else as close as possible to the target.
	 *
	 * @return list<Hex> cells to go through, the squadron's cell excluded; empty to stay
	 */
	private function findPath(BattleState $state, BattleSquadron $squadron, BattleSquadron $target, Randomizer $randomizer): array
	{
		$start = $squadron->cell;
		$range = $squadron->type->range;
		$cellsInRangeOfEnemies = $this->getCellsInRangeOfEnemies($state, $squadron);
		$previous = [$start->toKey() => null];
		$queue = [$start];
		$best = $start;
		$bestScore = $this->score($start, $target, $cellsInRangeOfEnemies, $range);

		// Breadth-first: the first cell in range of the target is the closest one, no need to explore further
		while ([] !== $queue && [0, 0] !== $bestScore) {
			$cell = array_shift($queue);
			// Equally short paths are picked at random: a fixed order would favour one orientation over the other
			foreach ($randomizer->shuffleArray($cell->neighbours()) as $neighbour) {
				if (array_key_exists($neighbour->toKey(), $previous) || !$this->canEnter($state, $squadron, $neighbour)) {
					continue;
				}

				$previous[$neighbour->toKey()] = $cell;
				$queue[] = $neighbour;
				$score = $this->score($neighbour, $target, $cellsInRangeOfEnemies, $range);

				// Breadth-first order: among equal scores, the first cell found is the closest
				if ($score < $bestScore) {
					$best = $neighbour;
					$bestScore = $score;
				}
			}
		}

		$path = [];
		for ($current = $best; !$current->equals($start); $current = $previous[$current->toKey()]) {
			$path[] = $current;
		}

		return array_reverse($path);
	}

	/**
	 * @param array<string, true> $cellsInRangeOfEnemies
	 *
	 * @return array{int, int} lower is better
	 */
	private function score(Hex $cell, BattleSquadron $target, array $cellsInRangeOfEnemies, int $range): array
	{
		$targetDistance = $cell->distanceTo($target->cell);

		if ($targetDistance <= $range) {
			return [0, 0];
		}

		if (isset($cellsInRangeOfEnemies[$cell->toKey()])) {
			return [1, 0];
		}

		return [2, $targetDistance];
	}

	/**
	 * Cells from which the squadron could fire on at least one enemy.
	 *
	 * @return array<string, true>
	 */
	private function getCellsInRangeOfEnemies(BattleState $state, BattleSquadron $squadron): array
	{
		$cells = [];

		foreach ($state->getEnemySquadrons($squadron->getFleet()->side) as $enemy) {
			foreach ($enemy->cell->range($squadron->type->range) as $cell) {
				$cells[$cell->toKey()] = true;
			}
		}

		return $cells;
	}

	private function canEnter(BattleState $state, BattleSquadron $squadron, Hex $cell): bool
	{
		return $state->isCellFree($cell) && $state->isCellOnBattlefield($cell);
	}
}
