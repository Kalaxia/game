<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;

/**
 * Picks the enemy squadron a squadron goes for: preferred ship classes first, then the closest,
 * then the weakest. Officer traits will alter this later (R4).
 */
readonly class SelectTarget
{
	public function __invoke(BattleState $state, BattleSquadron $squadron, bool $inRangeOnly): ?BattleSquadron
	{
		$best = null;
		$bestScore = null;

		foreach ($state->getEnemySquadrons($squadron->getFleet()->side) as $enemy) {
			$distance = $squadron->cell->distanceTo($enemy->cell);

			if ($inRangeOnly && $distance > $squadron->type->range) {
				continue;
			}

			$score = [$this->getPreferenceRank($squadron, $enemy), $distance, $enemy->ships, $enemy->identifier];

			if (null === $bestScore || $score < $bestScore) {
				$best = $enemy;
				$bestScore = $score;
			}
		}

		return $best;
	}

	private function getPreferenceRank(BattleSquadron $squadron, BattleSquadron $enemy): int
	{
		$rank = array_search($enemy->type->class, $squadron->type->preferredTargets, true);

		return false === $rank ? count($squadron->type->preferredTargets) : $rank;
	}
}
