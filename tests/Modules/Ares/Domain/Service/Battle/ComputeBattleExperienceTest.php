<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Ares\Domain\Service\Battle\ComputeBattleExperience;

class ComputeBattleExperienceTest extends BattleTestCase
{
	public function testExperienceComesFromDamageDealtAndTaken(): void
	{
		$a = self::fleet('a', 0, ['Corvette' => 14]);
		$b = self::fleet('b', 1, ['Corvette' => 14]);
		$state = self::battle([$a, $b]);
		(new BattleEngine())->resolveBattle($state);

		$experiences = (new ComputeBattleExperience(experiencePerDamage: 1.0))($state);

		// Same PEV and level: importance is 1
		static::assertSame($a->damageDealt + $a->damageTaken, $experiences['a']);
		static::assertSame($b->damageDealt + $b->damageTaken, $experiences['b']);
	}

	public function testFightingAStrongerEnemyEarnsMore(): void
	{
		$state = self::battle([
			self::fleet('weak', 0, ['Corvette' => 14], officerLevel: 1),
			self::fleet('strong', 1, ['Corvette' => 28], officerLevel: 3),
		]);
		(new BattleEngine())->resolveTurn($state);
		$computeExperience = new ComputeBattleExperience();

		static::assertSame(3.0, $computeExperience->getImportance($state, $state->fleets[0]), 'Importance is capped');
		static::assertEqualsWithDelta(98 * 2 / (196 * 4), $computeExperience->getImportance($state, $state->fleets[1]), 0.0001);
	}

	public function testDurabilityDoesNotInflateExperience(): void
	{
		$play = function (float $durability): int {
			$state = self::battle([self::fleet('a', 0, ['Destroyer' => 2]), self::fleet('b', 1, ['Destroyer' => 2])], durabilityFactor: $durability);
			(new BattleEngine())->resolveBattle($state);

			return (new ComputeBattleExperience())($state)['a'];
		};

		// Damage dealt and taken scale with durability, experience must not
		static::assertEqualsWithDelta($play(1.0), $play(3.0), $play(1.0) * 0.5);
	}
}
