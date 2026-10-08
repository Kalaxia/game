<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Officer experience earned in a battle (D6).
 *
 * Experience comes from the damage dealt to enemies plus the damage taken by the officer's own fleet,
 * weighted by the relative strength of the opponents (the "importance" of the battle):
 * fighting a stronger enemy earns more, crushing a weak one earns less.
 * Strength is the fleet PEV multiplied by (officer level + 1), summed over every enemy fleet.
 *
 * Damage is divided by the durability factor, so that tuning battle durations does not change experience.
 */
readonly class ComputeBattleExperience
{
	/** Fighting an enemy much stronger than yourself does not earn more than this multiplier */
	private const float MAX_IMPORTANCE = 3.0;

	public function __construct(
		#[Autowire('%ares.battle.experience_per_damage%')]
		private float $experiencePerDamage = 2.0,
	) {
	}

	/**
	 * @return array<string, int> experience by fleet identifier, for every fleet that took part
	 */
	public function __invoke(BattleState $state): array
	{
		$experiences = [];

		foreach ($state->getDeployedFleets() as $fleet) {
			$damage = ($fleet->damageDealt + $fleet->damageTaken) / $state->durabilityFactor;

			$experiences[$fleet->identifier] = (int) round($damage * $this->experiencePerDamage * $this->getImportance($state, $fleet));
		}

		return $experiences;
	}

	public function getImportance(BattleState $state, BattleFleet $fleet): float
	{
		$enemyStrength = array_sum(array_map(
			fn (BattleFleet $enemy) => $this->getStrength($enemy),
			array_filter($state->getDeployedFleets(), fn (BattleFleet $enemy) => $enemy->side !== $fleet->side),
		));

		return min(self::MAX_IMPORTANCE, $enemyStrength / max(1, $this->getStrength($fleet)));
	}

	private function getStrength(BattleFleet $fleet): int
	{
		return $fleet->getFormationPev() * ($fleet->officerLevel + 1);
	}
}
