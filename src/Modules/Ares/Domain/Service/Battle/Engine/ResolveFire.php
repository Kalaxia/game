<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use Random\Randomizer;

/**
 * Squadrons fire one after another in initiative order, so that damage dealt by a squadron
 * weakens its target before that target acts (R3).
 *
 * Damage and dodge follow the legacy rules:
 * - a shot misses when random(0, target speed) > 80;
 * - damage = ceil(ln(attack / defense + 1) × 4 × attack).
 * Attack uses the bonuses of the shooting fleet, defense and speed those of the target's fleet.
 * When the target is destroyed, the remaining shots go to the next target in range.
 *
 * The number of squadrons of a fleet firing in a round can be capped, so that the firepower of big
 * fleets saturates and big battles last longer (D1), while each reinforcement still adds firepower (G2). Squadrons left out accumulate their initiative,
 * so that every squadron eventually fires (R9).
 */
readonly class ResolveFire
{
	private const int DODGE_THRESHOLD = 80;

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

		foreach ($this->selectFiringSquadrons($state, $squadrons) as $squadron) {
			if ($squadron->isDestroyed()) {
				continue;
			}

			$shots = $this->getShots($squadron);

			while ([] !== $shots && null !== ($target = ($this->selectTarget)($state, $squadron, true))) {
				[$fired, $hits, $damage, $kills] = [0, 0, 0, 0];

				while ([] !== $shots && !$target->isDestroyed()) {
					$attack = $squadron->getFleet()->bonuses->applyToAttack($squadron->type->class, array_shift($shots));
					$targetBonuses = $target->getFleet()->bonuses;
					++$fired;

					if ($randomizer->getInt(0, (int) round($targetBonuses->applyToSpeed($target->type->class, $target->type->speed))) > self::DODGE_THRESHOLD) {
						continue;
					}

					++$hits;
					$defense = $targetBonuses->applyToDefense($target->type->class, $target->type->defense);
					$effectiveDamage = min($this->computeDamage($attack, $defense), $target->leadShipLife);
					$kills += $target->receiveDamage($effectiveDamage);
					$damage += $effectiveDamage;
				}

				$squadron->getFleet()->damageDealt += $damage;
				$target->getFleet()->damageTaken += $damage;
				$events[] = BattleEvent::squadronFired($squadron, $target, $fired, $hits, $damage, $kills);

				if ($target->isDestroyed()) {
					$state->removeSquadron($target);
					$events[] = BattleEvent::squadronDestroyed($target);
				}
			}
		}

		return $events;
	}

	/**
	 * @param list<BattleSquadron> $squadrons in initiative order
	 *
	 * @return list<BattleSquadron> squadrons allowed to fire this round, in firing order
	 */
	private function selectFiringSquadrons(BattleState $state, array $squadrons): array
	{
		if (null === $state->maxFiringSquadronsPerFleet) {
			return $squadrons;
		}

		$candidates = array_values(array_filter(
			$squadrons,
			fn (BattleSquadron $squadron) => !$squadron->isDestroyed() && null !== ($this->selectTarget)($state, $squadron, true),
		));
		usort(
			$candidates,
			fn (BattleSquadron $a, BattleSquadron $b) => ($b->type->initiative + $b->waitingInitiative) <=> ($a->type->initiative + $a->waitingInitiative),
		);

		$firingSquadrons = [];
		$firingCountByFleet = [];

		foreach ($candidates as $squadron) {
			$fleet = $squadron->getFleet()->identifier;

			if (($firingCountByFleet[$fleet] ?? 0) < $state->maxFiringSquadronsPerFleet) {
				$firingCountByFleet[$fleet] = ($firingCountByFleet[$fleet] ?? 0) + 1;
				$squadron->waitingInitiative = 0;
				$firingSquadrons[] = $squadron;
			} else {
				$squadron->waitingInitiative += $squadron->type->initiative;
			}
		}

		return $firingSquadrons;
	}

	/**
	 * @return list<int>
	 */
	private function getShots(BattleSquadron $squadron): array
	{
		return array_merge([], ...array_fill(0, $squadron->ships, $squadron->type->attack));
	}

	private function computeDamage(float $attack, float $defense): int
	{
		return (int) ceil(log($attack / max(1.0, $defense) + 1) * 4 * $attack);
	}
}
