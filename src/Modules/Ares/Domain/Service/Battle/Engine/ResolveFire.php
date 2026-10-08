<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle\Engine;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Model\Battle\OfficerTraitEffects;
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
 * A turn holds a limited number of actions (one action = one squadron firing), derived from the real
 * duration of a turn so that its animation is continuous. Actions are shared in turn among the fleets
 * able to fire, so that big battles last longer (D1) while each reinforcement brings its side more
 * actions (G2). Within a fleet, the squadron with the highest initiative acts first; squadrons left
 * out accumulate their initiative, so that every squadron eventually fires (R9).
 *
 * Only engaged fleets fire (D12). The fleet-size malus (D10) lowers the shooters' rate of fire
 * and the targets' dodge.
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

		$squadrons = array_values(array_filter($squadrons, fn (BattleSquadron $squadron) => $squadron->getFleet()->canFire()));

		foreach ($this->selectFiringSquadrons($state, $squadrons) as $squadron) {
			if ($squadron->isDestroyed()) {
				continue;
			}

			$shots = $this->getShots($squadron);

			// Split fire: half of the shots for the best target, the other half for the second best (Q12)
			$targets = $squadron->type->splitFire ? array_slice($this->selectTarget->getCandidates($state, $squadron, true), 0, 2) : [];
			if (2 === count($targets)) {
				$firstHalf = array_splice($shots, 0, (int) ceil(count($shots) / 2));
				$this->fireVolley($state, $squadron, $targets[0], $firstHalf, $randomizer, $events);
				$shots = [...$firstHalf, ...$shots];

				if (!$targets[1]->isDestroyed()) {
					$this->fireVolley($state, $squadron, $targets[1], $shots, $randomizer, $events);
				}
			}

			while ([] !== $shots && null !== ($target = ($this->selectTarget)($state, $squadron, true))) {
				$this->fireVolley($state, $squadron, $target, $shots, $randomizer, $events);
			}
		}

		return $events;
	}

	/**
	 * Fires shots at a target until they run out or the target is destroyed. Area weapons also hit
	 * the squadrons next to the target, allies included (Q12).
	 *
	 * @param list<int>         $shots  remaining shots, consumed
	 * @param list<BattleEvent> $events
	 */
	private function fireVolley(BattleState $state, BattleSquadron $squadron, BattleSquadron $target, array &$shots, Randomizer $randomizer, array &$events): void
	{
		[$fired, $hits, $damage, $kills] = [0, 0, 0, 0];
		/** @var array<string, array{BattleSquadron, int, int}> $splashed damage and kills by squadron hit by area damage */
		$splashed = [];

		while ([] !== $shots && !$target->isDestroyed()) {
			$attack = $squadron->getFleet()->bonuses->applyToAttack($squadron->type->class, array_shift($shots))
				* OfficerTraitEffects::getAttackFactor($squadron->getFleet());
			$targetBonuses = $target->getFleet()->bonuses;
			++$fired;

			$targetSpeed = $targetBonuses->applyToSpeed($target->type->class, $target->type->speed)
				* (1 - $target->getFleet()->sizeMalus)
				* OfficerTraitEffects::getDodgeFactor($target->getFleet());
			if ($randomizer->getInt(0, (int) round($targetSpeed)) > self::DODGE_THRESHOLD) {
				continue;
			}

			++$hits;
			$defense = $targetBonuses->applyToDefense($target->type->class, $target->type->defense)
				* OfficerTraitEffects::getDefenseFactor($state, $target->getFleet());
			$rawDamage = (int) ceil($this->computeDamage($attack, $defense) * OfficerTraitEffects::getDamageFactor($state, $squadron, $target));
			$effectiveDamage = min($rawDamage, $target->leadShipLife);
			$kills += $target->receiveDamage($effectiveDamage);
			$damage += $effectiveDamage;

			if ($squadron->type->splashRatio > 0) {
				$this->splash($state, $squadron, $target, (int) ceil($effectiveDamage * $squadron->type->splashRatio), $splashed);
			}
		}

		$this->account($squadron, $target, $damage, $kills);
		$events[] = BattleEvent::squadronFired($squadron, $target, $fired, $hits, $damage, $kills);
		$this->removeIfDestroyed($state, $target, $events);

		foreach ($splashed as [$victim, $splashDamage, $splashKills]) {
			$this->account($squadron, $victim, $splashDamage, $splashKills);
			$events[] = BattleEvent::squadronSplashed($squadron, $victim, $splashDamage, $splashKills);
			$this->removeIfDestroyed($state, $victim, $events);
		}
	}

	/**
	 * @param array<string, array{BattleSquadron, int, int}> $splashed
	 */
	private function splash(BattleState $state, BattleSquadron $squadron, BattleSquadron $target, int $damage, array &$splashed): void
	{
		foreach ($target->cell->neighbours() as $cell) {
			$victim = $state->getSquadronAt($cell);

			if (null === $victim || $victim === $squadron || $victim->isDestroyed()) {
				continue;
			}

			$effectiveDamage = min($damage, $victim->leadShipLife);
			$kills = $victim->receiveDamage($effectiveDamage);
			[, $previousDamage, $previousKills] = $splashed[$victim->identifier] ?? [$victim, 0, 0];
			$splashed[$victim->identifier] = [$victim, $previousDamage + $effectiveDamage, $previousKills + $kills];
		}
	}

	/**
	 * Damage and kills count for the shooter only against enemies: hitting allies brings nothing.
	 */
	private function account(BattleSquadron $squadron, BattleSquadron $victim, int $damage, int $kills): void
	{
		$victim->getFleet()->damageTaken += $damage;

		if ($victim->getFleet()->side !== $squadron->getFleet()->side) {
			$squadron->getFleet()->damageDealt += $damage;
			$squadron->getFleet()->pevDestroyed += $kills * $victim->type->pev;
		}
	}

	/**
	 * @param list<BattleEvent> $events
	 */
	private function removeIfDestroyed(BattleState $state, BattleSquadron $squadron, array &$events): void
	{
		if ($squadron->isDestroyed() && $state->getSquadronAt($squadron->cell) === $squadron) {
			$state->removeSquadron($squadron);
			$events[] = BattleEvent::squadronDestroyed($squadron);
		}
	}

	/**
	 * @param list<BattleSquadron> $squadrons in initiative order
	 *
	 * @return list<BattleSquadron> squadrons allowed to fire this round, in firing order
	 */
	private function selectFiringSquadrons(BattleState $state, array $squadrons): array
	{
		if (null === $state->maxActionsPerTurn) {
			return $squadrons;
		}

		$candidates = array_values(array_filter(
			$squadrons,
			fn (BattleSquadron $squadron) => !$squadron->isDestroyed() && null !== ($this->selectTarget)($state, $squadron, true),
		));
		usort($candidates, fn (BattleSquadron $a, BattleSquadron $b) => $this->getPriority($b) <=> $this->getPriority($a));

		// Queues of candidates by fleet, fleets ordered by their most urgent squadron; tacticians are served first (Q13)
		$queues = [];
		foreach ($candidates as $squadron) {
			$queues[$squadron->getFleet()->identifier][] = $squadron;
		}
		uksort($queues, fn (string $a, string $b) => (int) $queues[$b][0]->getFleet()->hasTrait(OfficerTrait::Tactician) <=> (int) $queues[$a][0]->getFleet()->hasTrait(OfficerTrait::Tactician));

		// Actions are handed out in turn to each fleet
		$firingSquadrons = [];
		while (count($firingSquadrons) < $state->maxActionsPerTurn && [] !== $queues) {
			foreach (array_keys($queues) as $fleet) {
				if (count($firingSquadrons) >= $state->maxActionsPerTurn) {
					break;
				}

				$firingSquadrons[] = array_shift($queues[$fleet]);
				if ([] === $queues[$fleet]) {
					unset($queues[$fleet]);
				}
			}
		}

		// Chosen squadrons fire in order of priority
		usort($firingSquadrons, fn (BattleSquadron $a, BattleSquadron $b) => $this->getPriority($b) <=> $this->getPriority($a));

		foreach ($candidates as $squadron) {
			if (in_array($squadron, $firingSquadrons, true)) {
				$squadron->waitingInitiative = 0;
			} else {
				$squadron->waitingInitiative += $squadron->getInitiative();
			}
		}

		return $firingSquadrons;
	}

	private function getPriority(BattleSquadron $squadron): int
	{
		return $squadron->getInitiative() + $squadron->waitingInitiative;
	}

	/**
	 * @return list<int>
	 */
	private function getShots(BattleSquadron $squadron): array
	{
		$shots = array_merge([], ...array_fill(0, $squadron->ships, $squadron->type->attack));
		// A disorganised fleet fires less (D10)
		$count = (int) ceil(count($shots) * (1 - $squadron->getFleet()->sizeMalus));

		return array_slice($shots, 0, max(1, $count));
	}

	private function computeDamage(float $attack, float $defense): int
	{
		return (int) ceil(log($attack / max(1.0, $defense) + 1) * 4 * $attack);
	}
}
