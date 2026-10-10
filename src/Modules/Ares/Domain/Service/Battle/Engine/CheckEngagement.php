<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleState;

/**
 * Engagement delay (D11): when the attackers are outnumbered, nobody fires for a while.
 *
 * Fleets still deploy and move during the delay, and reinforcements may arrive. The delay ends
 * early as soon as the attackers reach the minimum force ratio. Once started, the engagement lasts
 * until the end of the battle.
 */
readonly class CheckEngagement
{
	/**
	 * @param list<BattleEvent> $events events of the delay starting or ending, if any
	 *
	 * @return bool whether fleets may fire this turn
	 */
	public function __invoke(BattleState $state, array &$events): bool
	{
		$rules = $state->rules->engagement;

		if (null === $rules || null !== $state->engagedAtTurn) {
			return true;
		}

		$defenders = 0;
		$attackers = 0;
		foreach ($state->getActiveFleets() as $fleet) {
			if ($fleet->side === $rules->defendingSide) {
				$defenders += $fleet->getPev();
			} else {
				$attackers += $fleet->getPev();
			}
		}

		// No battle yet: nothing to delay
		if (0 === $defenders || 0 === $attackers) {
			return true;
		}

		$isOutnumbered = $attackers < $defenders * $rules->minForceRatio;

		if ($isOutnumbered) {
			if (null === $state->engagementDelayedAtTurn) {
				$state->engagementDelayedAtTurn = $state->turn;
				$events[] = BattleEvent::engagementDelayed($state->turn + $rules->delayTurns - 1);
			}

			if ($state->turn - $state->engagementDelayedAtTurn < $rules->delayTurns) {
				return false;
			}
		}

		$state->engagedAtTurn = $state->turn;
		if (null !== $state->engagementDelayedAtTurn) {
			$events[] = BattleEvent::engagementStarted();
		}

		return true;
	}
}
