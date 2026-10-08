<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\OfficerTraitEffects;

/**
 * Picks the enemy squadron a squadron goes for: preferred ship classes first, then the closest,
 * then the weakest. Officer traits alter the preferences (Q13).
 */
readonly class SelectTarget
{
	public function __invoke(BattleState $state, BattleSquadron $squadron, bool $inRangeOnly): ?BattleSquadron
	{
		return $this->getCandidates($state, $squadron, $inRangeOnly)[0] ?? null;
	}

	/**
	 * Enemy squadrons ordered from the best target to the worst. In range means between the minimum
	 * and the maximum range of the squadron's weapons (Q12).
	 *
	 * @return list<BattleSquadron>
	 */
	public function getCandidates(BattleState $state, BattleSquadron $squadron, bool $inRangeOnly): array
	{
		$candidates = [];

		foreach ($state->getEnemySquadrons($squadron->getFleet()->side) as $enemy) {
			$distance = $squadron->cell->distanceTo($enemy->cell);

			if ($inRangeOnly && !$squadron->type->isInRange($distance)) {
				continue;
			}

			$candidates[] = [[$this->getPreferenceRank($squadron, $enemy, $inRangeOnly), $distance, $enemy->ships, $enemy->identifier], $enemy];
		}

		usort($candidates, fn (array $a, array $b) => $a[0] <=> $b[0]);

		return array_column($candidates, 1);
	}

	/**
	 * An aggressive officer charges the closest enemy, but still fires on the preferred targets in range.
	 */
	private function getPreferenceRank(BattleSquadron $squadron, BattleSquadron $enemy, bool $inRangeOnly): int
	{
		if (!$inRangeOnly && OfficerTraitEffects::ignoresTargetPreferences($squadron->getFleet())) {
			return 0;
		}

		$preferredTargets = OfficerTraitEffects::getPreferredTargets($squadron, $inRangeOnly);
		$rank = array_search($enemy->type->class, $preferredTargets, true);

		return false === $rank ? count($preferredTargets) : $rank;
	}
}
