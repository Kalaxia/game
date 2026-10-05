<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleBonuses;
use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;

class BattleEngineTest extends BattleTestCase
{
	public function testFleetsDeployInContactAndFaceEachOther(): void
	{
		$attacker = self::fleet('attacker', 0, ['Corvette' => 14]);
		$defender = self::fleet('defender', 1, ['Corvette' => 14]);
		$state = self::battle([$attacker, $defender]);

		(new BattleEngine())->resolveTurn($state);

		static::assertTrue($attacker->isDeployed());
		static::assertTrue($defender->isDeployed());
		static::assertSame(1, $attacker->cluster->distanceTo($defender->cluster));
		static::assertSame($attacker->orientation->opposite(), $defender->orientation);
	}

	public function testOverwhelmingFleetWins(): void
	{
		$strong = self::fleet('strong', 0, ['HeavyCorvette' => 44, 'Corvette' => 28]);
		$weak = self::fleet('weak', 1, ['LightFighter' => 10]);
		$state = self::battle([$strong, $weak]);

		$turns = (new BattleEngine())->resolveBattle($state);

		static::assertTrue($state->isOver);
		static::assertSame(0, $state->winningSide);
		static::assertTrue($weak->isDestroyed());
		static::assertFalse($strong->isDestroyed());
		static::assertTrue(end($turns)->hasEndedBattle());
		static::assertLessThan($state->maxTurns, $state->turn);
	}

	public function testBattleIsDeterministicForAGivenSeed(): void
	{
		$play = fn (int $seed) => array_map(
			fn (TurnResult $turn) => $turn->toArray(),
			(new BattleEngine())->resolveBattle(self::battle([
				self::fleet('a', 0, ['LightFighter' => 50, 'Corvette' => 14, 'Frigate' => 2]),
				self::fleet('b', 1, ['Fighter' => 33, 'LightFrigate' => 8, 'Bomber' => 25]),
			], $seed)),
		);

		static::assertSame(json_encode($play(1234)), json_encode($play(1234)));
		static::assertNotSame(json_encode($play(1234)), json_encode($play(98765)));
	}

	public function testReinforcementJoinsAtItsArrivalTurn(): void
	{
		$defender = self::fleet('defender', 1, ['Destroyer' => 10]);
		$attacker = self::fleet('attacker', 0, ['Destroyer' => 10]);
		$reinforcement = self::fleet('reinforcement', 0, ['Destroyer' => 10], arrivalTurn: 3);
		$state = self::battle([$defender, $attacker, $reinforcement]);
		$engine = new BattleEngine();

		$engine->resolveTurn($state);
		$engine->resolveTurn($state);
		static::assertFalse($reinforcement->isDeployed());

		$turn = $engine->resolveTurn($state);

		static::assertTrue($reinforcement->isDeployed());
		static::assertContains('reinforcement', $this->getFleetsJoined($turn));
		// Placed in contact with the enemy
		static::assertSame(1, $reinforcement->cluster->distanceTo($defender->cluster));
	}

	public function testBattleWaitsForPendingReinforcements(): void
	{
		$alone = self::fleet('alone', 0, ['Corvette' => 5]);
		$late = self::fleet('late', 1, ['Corvette' => 5], arrivalTurn: 4);
		$state = self::battle([$alone, $late]);
		$engine = new BattleEngine();

		for ($i = 0; $i < 3; ++$i) {
			static::assertFalse($engine->resolveTurn($state)->hasEndedBattle());
		}

		static::assertContains('late', $this->getFleetsJoined($engine->resolveTurn($state)));
	}

	public function testOnlyFightersAndBombersLeaveTheirFleetArea(): void
	{
		$fleets = [
			self::fleet('a', 0, ['Destroyer' => 3, 'LightFighter' => 50, 'Bomber' => 25]),
			self::fleet('b', 1, ['Destroyer' => 3, 'Cruiser' => 3]),
		];
		$state = self::battle($fleets);
		$engine = new BattleEngine();

		for ($i = 0; $i < 10 && !$state->isOver; ++$i) {
			$engine->resolveTurn($state);

			foreach ($state->getActiveSquadrons() as $squadron) {
				if (!$squadron->type->role->canLeaveFleetArea()) {
					static::assertTrue(
						$state->grid->clusterOf($squadron->cell)->equals($squadron->getFleet()->cluster),
						sprintf('Squadron %s left its fleet area', $squadron->identifier),
					);
				}
			}
		}
	}

	public function testNoTwoSquadronsShareACell(): void
	{
		$state = self::battle([
			self::fleet('a', 0, ['LightFighter' => 150, 'Bomber' => 50]),
			self::fleet('b', 1, ['Fighter' => 99, 'LightCorvette' => 40]),
			self::fleet('c', 1, ['HeavyFighter' => 66], arrivalTurn: 2),
		]);
		$engine = new BattleEngine();

		while (!$state->isOver) {
			$engine->resolveTurn($state);
			$cells = array_map(fn (BattleSquadron $squadron) => $squadron->cell->toKey(), $state->getActiveSquadrons());

			static::assertCount(count($cells), array_unique($cells));
		}
	}

	public function testMirrorBattlesAreFair(): void
	{
		$ships = ['LightFighter' => 50, 'Corvette' => 28, 'LightFrigate' => 8, 'Destroyer' => 2];
		$firstDeployedWins = 0;
		$runs = 100;

		for ($seed = 0; $seed < $runs; ++$seed) {
			$state = self::battle([self::fleet('first', 0, $ships), self::fleet('second', 1, $ships)], $seed);
			(new BattleEngine())->resolveBattle($state);

			$firstDeployedWins += 0 === $state->winningSide ? 1 : 0;
		}

		// Wide bounds: this only catches a systematic advantage, such as a movement bias tied to the orientation
		static::assertGreaterThan(35, $firstDeployedWins);
		static::assertLessThan(65, $firstDeployedWins);
	}

	public function testDurabilityFactorMultipliesShipLife(): void
	{
		$fleet = self::fleet('a', 0, ['Destroyer' => 1]);
		$state = self::battle([$fleet, self::fleet('b', 1, ['Destroyer' => 1], arrivalTurn: 99)], durabilityFactor: 2.5);

		(new BattleEngine())->resolveTurn($state);

		static::assertSame(3000, $fleet->squadrons[0]->maxShipLife);
		static::assertSame(3000, $fleet->squadrons[0]->leadShipLife);
	}

	public function testFiringSquadronsAreCappedPerFleet(): void
	{
		$state = self::battle([
			self::fleet('a', 0, ['Destroyer' => 6]),
			self::fleet('b', 1, ['Destroyer' => 6]),
		], maxFiringSquadronsPerFleet: 2);
		$engine = new BattleEngine();

		while (!$state->isOver) {
			$firingByFleet = [];
			foreach ($engine->resolveTurn($state)->events as $event) {
				if (BattleEvent::SQUADRON_FIRED === $event->type) {
					$firingByFleet[substr($event->payload['attacker'], 0, 1)][$event->payload['attacker']] = true;
				}
			}

			foreach ($firingByFleet as $squadrons) {
				static::assertLessThanOrEqual(2, count($squadrons));
			}
		}
	}

	public function testSquadronsLeftOutEventuallyFire(): void
	{
		// Light fighters have a much higher initiative than the cruiser: the cruiser must still fire thanks to the initiative it accumulates
		$attacker = self::fleet('a', 0, ['LightFighter' => 100, 'Cruiser' => 1]);
		$state = self::battle([$attacker, self::fleet('b', 1, ['Destroyer' => 10])], durabilityFactor: 5.0, maxFiringSquadronsPerFleet: 2);
		$engine = new BattleEngine();
		$cruiserFired = false;

		for ($i = 0; $i < 30 && !$state->isOver && !$cruiserFired; ++$i) {
			foreach ($engine->resolveTurn($state)->events as $event) {
				if (BattleEvent::SQUADRON_FIRED === $event->type && 'a-2' === $event->payload['attacker']) {
					$cruiserFired = true;
				}
			}
		}

		static::assertSame('Cruiser', $attacker->squadrons[2]->type->identifier);
		static::assertTrue($cruiserFired);
	}

	public function testTargetDefenseUsesTheTargetFleetBonuses(): void
	{
		$play = function (int $defenseBonus): int {
			$shooter = self::fleet('a', 0, ['Frigate' => 2]);
			$target = new BattleFleet(
				identifier: 'b',
				name: 'b',
				faction: 2,
				side: 1,
				officerLevel: 1,
				formation: self::fleet('b', 1, ['Destroyer' => 1])->formation,
				bonuses: (new BattleBonuses())->add(ShipClass::Destroyer, defense: $defenseBonus),
			);
			$state = self::battle([$shooter, $target], durabilityFactor: 100.0);
			$engine = new BattleEngine();

			for ($i = 0; $i < 10; ++$i) {
				$engine->resolveTurn($state);
			}

			return $shooter->damageDealt;
		};

		// The shooter's own bonuses must not change the target's defense: a sturdier target takes less damage
		static::assertGreaterThan($play(200), $play(0));
	}

	public function testDamageIsAccountedForExperience(): void
	{
		$a = self::fleet('a', 0, ['Corvette' => 14]);
		$b = self::fleet('b', 1, ['Corvette' => 14]);
		$state = self::battle([$a, $b]);

		(new BattleEngine())->resolveBattle($state);

		static::assertGreaterThan(0, $a->damageDealt);
		static::assertSame($a->damageDealt, $b->damageTaken);
		static::assertSame($b->damageDealt, $a->damageTaken);
	}

	/**
	 * @return list<string>
	 */
	private function getFleetsJoined(TurnResult $turn): array
	{
		return array_values(array_map(
			fn (BattleEvent $event) => $event->payload['fleet'],
			array_filter($turn->events, fn (BattleEvent $event) => BattleEvent::FLEET_JOINED === $event->type),
		));
	}
}
