<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use Random\Randomizer;

/**
 * Squadrons with no enemy in range move toward their target, in initiative order (R4).
 *
 * The first squadron reaching a free cell holds it. Only fighters and bombers may leave
 * their fleet's area (D4).
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

			$from = $squadron->cell;

			for ($step = 0; $step < $squadron->type->movement; ++$step) {
				if ($squadron->cell->distanceTo($target->cell) <= $squadron->type->range) {
					break;
				}

				if (null === ($next = $this->findNextCell($state, $squadron, $target->cell, $randomizer))) {
					break;
				}

				$state->placeSquadron($squadron, $next);
			}

			if (!$from->equals($squadron->cell)) {
				$events[] = BattleEvent::squadronMoved($squadron, $from);
			}
		}

		return $events;
	}

	private function findNextCell(BattleState $state, BattleSquadron $squadron, Hex $destination, Randomizer $randomizer): ?Hex
	{
		$best = null;
		$bestDistance = $squadron->cell->distanceTo($destination);

		// Equally good cells are picked at random: a fixed order would favour one orientation over the other
		foreach ($randomizer->shuffleArray($squadron->cell->neighbours()) as $cell) {
			if (!$this->canEnter($state, $squadron, $cell)) {
				continue;
			}

			$distance = $cell->distanceTo($destination);

			if ($distance < $bestDistance) {
				$best = $cell;
				$bestDistance = $distance;
			}
		}

		return $best;
	}

	private function canEnter(BattleState $state, BattleSquadron $squadron, Hex $cell): bool
	{
		if (!$state->isCellFree($cell) || !$state->isCellOnBattlefield($cell)) {
			return false;
		}

		return $squadron->type->role->canLeaveFleetArea()
			|| $state->grid->clusterOf($cell)->equals($squadron->getFleet()->cluster);
	}
}
