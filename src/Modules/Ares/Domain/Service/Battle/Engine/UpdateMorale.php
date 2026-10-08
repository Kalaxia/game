<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\FleetStatus;
use App\Modules\Ares\Domain\Model\Battle\OfficerTraitEffects;
use App\Modules\Ares\Domain\Model\Battle\Rules\MoraleRules;

/**
 * Morale and retreat (D12), at the end of each turn.
 *
 * - Morale drops with the PEV lost during the turn and while the fleet's side is outnumbered.
 * - It rises with the enemy PEV the fleet destroyed during the turn.
 * - Officer traits change the thresholds, the losses and the recovery (Q13, see OfficerTraitEffects).
 * - It recovers when the fleet took no losses, faster next to an allied fleet.
 * - Below the withdrawal threshold, the fleet withdraws behind allied fleets, or flees if it has none.
 *   A withdrawn fleet does not fire and remobilises above the remobilisation threshold.
 * - Below the flight threshold, the fleet flees: it does not fire, stays exposed for a few turns,
 *   then leaves the battle with its surviving ships.
 */
readonly class UpdateMorale
{
	/**
	 * @param array<string, int> $pevAtTurnStart          PEV of each active fleet before fire
	 * @param array<string, int> $pevDestroyedAtTurnStart enemy PEV each fleet had destroyed before fire
	 *
	 * @return list<BattleEvent>
	 */
	public function __invoke(BattleState $state, array $pevAtTurnStart, array $pevDestroyedAtTurnStart = []): array
	{
		$rules = $state->rules->morale;

		if (null === $rules) {
			return [];
		}

		$events = [];
		$fleets = $state->getActiveFleets();

		foreach ($fleets as $fleet) {
			$this->updateMorale(
				$state,
				$rules,
				$fleet,
				$pevAtTurnStart[$fleet->identifier] ?? $fleet->getPev(),
				$fleet->pevDestroyed - ($pevDestroyedAtTurnStart[$fleet->identifier] ?? $fleet->pevDestroyed),
			);
		}

		foreach ($fleets as $fleet) {
			$event = $this->updateStatus($state, $rules, $fleet);

			if (null !== $event) {
				$events[] = $event;
			}
		}

		if ([] !== $fleets) {
			$events[] = BattleEvent::moraleUpdated($fleets);
		}

		return $events;
	}

	private function updateMorale(BattleState $state, MoraleRules $rules, BattleFleet $fleet, int $pevAtTurnStart, int $pevDestroyed): void
	{
		$lostPev = $pevAtTurnStart - $fleet->getPev();
		$morale = $fleet->morale;
		// Inflicting losses heartens the crews, relatively to the size of the fleet
		$morale += $pevDestroyed / max(1, $fleet->getFormationPev()) * 100 * $rules->victoryFactor;

		if ($lostPev > 0) {
			$morale -= $lostPev / max(1, $fleet->getFormationPev()) * 100 * OfficerTraitEffects::getLossFactor($fleet, $rules);
		} else {
			$morale += $rules->recovery + OfficerTraitEffects::getAlliedRecovery($state, $fleet, $rules);
		}

		$ownPev = $this->getSidePev($state, $fleet->side, true);
		$enemyPev = $this->getSidePev($state, $fleet->side, false);
		if ($ownPev > 0 && $enemyPev > $ownPev) {
			$floor = OfficerTraitEffects::getOutnumberedMoraleFloor($fleet);
			$penalty = $rules->outnumberedPenalty * min(2, $enemyPev / $ownPev - 1);
			// Being outnumbered never takes the morale below the officer's floor, nor lowers it further
			$morale = $morale > $floor ? max($floor, $morale - $penalty) : $morale;
		}

		$fleet->morale = max(0.0, min(100.0, $morale));
	}

	private function updateStatus(BattleState $state, MoraleRules $rules, BattleFleet $fleet): ?BattleEvent
	{
		switch ($fleet->status) {
			case FleetStatus::Fleeing:
				if ($state->turn - $fleet->fleeingSinceTurn >= OfficerTraitEffects::getExposedTurns($fleet, $rules)) {
					return $this->leaveBattle($state, $fleet);
				}

				return null;

			case FleetStatus::Withdrawn:
				if ($fleet->morale < $rules->flightThreshold) {
					return $this->flee($state, $fleet);
				}
				if ($fleet->morale >= OfficerTraitEffects::getRemobilisationThreshold($fleet, $rules)) {
					$fleet->status = FleetStatus::Engaged;

					return BattleEvent::fleetRemobilised($fleet);
				}

				return null;

			case FleetStatus::Engaged:
				if ($fleet->morale < $rules->flightThreshold) {
					return $this->flee($state, $fleet);
				}
				if ($fleet->morale < OfficerTraitEffects::getWithdrawalThreshold($fleet, $rules)) {
					if (!$this->hasEngagedAlly($state, $fleet)) {
						return $this->flee($state, $fleet);
					}
					$fleet->status = FleetStatus::Withdrawn;

					return BattleEvent::fleetWithdrew($fleet);
				}

				return null;

			default:
				return null;
		}
	}

	private function flee(BattleState $state, BattleFleet $fleet): BattleEvent
	{
		$fleet->status = FleetStatus::Fleeing;
		$fleet->fleeingSinceTurn = $state->turn;

		return BattleEvent::fleetFleeing($fleet);
	}

	private function leaveBattle(BattleState $state, BattleFleet $fleet): BattleEvent
	{
		$event = BattleEvent::fleetFled($fleet);

		foreach ($fleet->getActiveSquadrons() as $squadron) {
			$state->removeSquadron($squadron);
		}
		$fleet->status = FleetStatus::Fled;

		return $event;
	}

	private function hasEngagedAlly(BattleState $state, BattleFleet $fleet): bool
	{
		foreach ($state->getActiveFleets() as $ally) {
			if ($ally !== $fleet && $ally->side === $fleet->side && $ally->canFire()) {
				return true;
			}
		}

		return false;
	}

	private function getSidePev(BattleState $state, int $side, bool $own): int
	{
		return array_sum(array_map(
			fn (BattleSquadron $squadron) => $squadron->getPev(),
			array_filter($state->getActiveSquadrons(), fn (BattleSquadron $squadron) => ($squadron->getFleet()->side === $side) === $own),
		));
	}
}
