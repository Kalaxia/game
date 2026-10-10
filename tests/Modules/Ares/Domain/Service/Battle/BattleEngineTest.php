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
				self::fleet('b', 1, ['Fighter' => 33, 'LightFrigate' => 8, 'HeavyFighter' => 25]),
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

	public function testFormationIsKeptAsComposedWhenDeployed(): void
	{
		// Both fleets arrive on the same turn: each one must face the other from the start
		$a = self::fleet('a', 0, ['Corvette' => 28, 'Destroyer' => 2, 'Frigate' => 4]);
		$b = self::fleet('b', 1, ['LightFighter' => 50, 'Cruiser' => 1]);
		$state = self::battle([$a, $b]);

		$turn = (new BattleEngine())->resolveTurn($state);

		static::assertSame($a->orientation->opposite(), $b->orientation);
		foreach ($turn->events as $event) {
			if (BattleEvent::FLEET_JOINED !== $event->type) {
				continue;
			}

			$fleet = 'a' === $event->payload['fleet'] ? $a : $b;
			$center = $state->grid->clusterCenter($fleet->cluster);

			foreach ($event->payload['squadrons'] as $index => $squadron) {
				$expected = $center->add($fleet->formation[$index]->offset->rotate($fleet->orientation->value));

				static::assertSame(['q' => $expected->q, 'r' => $expected->r], $squadron['cell'], sprintf('Squadron %s left its formation cell', $squadron['squadron']));
			}
		}
	}

	public function testHeavyShipsStrikeTheRearOfAnEngagedFleet(): void
	{
		// The reinforcement arrives behind b, already engaged with a: its destroyers must reach b's squadrons
		$reinforcement = self::fleet('c', 0, ['Destroyer' => 6], arrivalTurn: 4);
		$state = self::battle([
			self::fleet('a', 0, ['Destroyer' => 8]),
			self::fleet('b', 1, ['Destroyer' => 8]),
			$reinforcement,
		], durabilityFactor: 3.0, maxActionsPerTurn: 6);
		$engine = new BattleEngine();

		for ($i = 0; $i < 25 && !$state->isOver; ++$i) {
			$engine->resolveTurn($state);
		}

		static::assertGreaterThan(0, $reinforcement->damageDealt);
	}

	public function testNoTwoSquadronsShareACell(): void
	{
		$state = self::battle([
			self::fleet('a', 0, ['LightFighter' => 150, 'HeavyFighter' => 50]),
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

	public function testActionsPerTurnAreCappedAndShared(): void
	{
		$state = self::battle([
			self::fleet('a', 0, ['Destroyer' => 6]),
			self::fleet('b', 1, ['Destroyer' => 6]),
		], maxActionsPerTurn: 4);
		$engine = new BattleEngine();
		$sharedTurns = 0;

		while (!$state->isOver) {
			$firingByFleet = [];
			foreach ($engine->resolveTurn($state)->events as $event) {
				if (BattleEvent::SQUADRON_FIRED === $event->type) {
					$firingByFleet[substr($event->payload['attacker'], 0, 1)][$event->payload['attacker']] = true;
				}
			}

			static::assertLessThanOrEqual(4, array_sum(array_map('count', $firingByFleet)));
			// Actions are handed out in turn: a fleet only gets more than its share when the other cannot use it
			if (2 === count($firingByFleet['a'] ?? []) && 2 === count($firingByFleet['b'] ?? [])) {
				++$sharedTurns;
			}
		}

		static::assertGreaterThan(0, $sharedTurns);
	}

	public function testSquadronsLeftOutEventuallyFire(): void
	{
		// Light fighters have a much higher initiative than the cruiser: the cruiser must still fire thanks to the initiative it accumulates
		$attacker = self::fleet('a', 0, ['LightFighter' => 100, 'Cruiser' => 1]);
		$state = self::battle([$attacker, self::fleet('b', 1, ['Destroyer' => 10])], durabilityFactor: 5.0, maxActionsPerTurn: 6);
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

	public function testVictoriousFleetGoesToHelpItsAllies(): void
	{
		// a2 quickly destroys b2, then must reach b1, still fighting a1, instead of waiting
		$a2 = self::fleet('a2', 0, ['Destroyer' => 8]);
		$state = self::battle([
			self::fleet('a1', 0, ['Destroyer' => 8]),
			self::fleet('b1', 1, ['Destroyer' => 8]),
			$a2,
			self::fleet('b2', 1, ['LightFrigate' => 4]),
		], durabilityFactor: 3.0, maxActionsPerTurn: 6);
		$engine = new BattleEngine();
		$b2DestroyedAt = null;
		$a2FiredOnB1 = false;

		while (!$state->isOver) {
			$turn = $engine->resolveTurn($state);

			foreach ($turn->events as $event) {
				if (BattleEvent::FLEET_DESTROYED === $event->type && 'b2' === $event->payload['fleet']) {
					$b2DestroyedAt = $turn->turn;
				}
				if (null !== $b2DestroyedAt && BattleEvent::SQUADRON_FIRED === $event->type
					&& str_starts_with($event->payload['attacker'], 'a2') && str_starts_with($event->payload['target'], 'b1')) {
					$a2FiredOnB1 = true;
				}
			}
		}

		static::assertNotNull($b2DestroyedAt, 'The destruction of b2 must be reported');
		static::assertTrue($a2FiredOnB1);
		static::assertGreaterThan(0, $a2->damageDealt);
	}

	public function testDestroyedFleetFreesItsArea(): void
	{
		$weak = self::fleet('weak', 1, ['LightFighter' => 1]);
		$state = self::battle([self::fleet('strong', 0, ['HeavyCorvette' => 44]), $weak, self::fleet('late', 1, ['Corvette' => 1], arrivalTurn: 50)]);
		$engine = new BattleEngine();

		while (!$weak->isDestroyed()) {
			$engine->resolveTurn($state);
		}

		static::assertNull($state->getFleetOnCluster($weak->cluster));
	}

	public function testFleetDestroyedOnItsArrivalTurnIsReported(): void
	{
		$state = self::battle([self::fleet('strong', 0, ['HeavyCorvette' => 44]), self::fleet('doomed', 1, ['LightFighter' => 1], arrivalTurn: 3)]);
		$engine = new BattleEngine();
		$reported = [];

		while (!$state->isOver) {
			foreach ($engine->resolveTurn($state)->events as $event) {
				if (BattleEvent::FLEET_DESTROYED === $event->type) {
					$reported[] = $event->payload['fleet'];
				}
			}
		}

		static::assertSame(['doomed'], $reported);
	}

	public function testFleetDoesNotMoveOntoItsOwnSquadronsLeftOutsideItsArea(): void
	{
		// Regression: with morale, retreats and roaming squadrons, a moving fleet area used to land on
		// a squadron of the same fleet standing outside the area
		$engine = new BattleEngine();
		foreach ([3, 7, 11, 19, 23] as $seed) {
			$state = new \App\Modules\Ares\Domain\Model\Battle\BattleState(
				grid: new \App\Modules\Ares\Domain\Model\Battle\Grid\ClusteredHexGrid(4),
				battlefieldSize: 5,
				seed: $seed,
				fleets: [
					self::fleet('a1', 0, ['LightFighter' => 100, 'Corvette' => 28, 'LightFrigate' => 8, 'Destroyer' => 2]),
					self::fleet('a2', 0, ['LightFighter' => 100, 'Corvette' => 28, 'LightFrigate' => 8, 'Destroyer' => 2]),
					self::fleet('b1', 1, ['LightFighter' => 100, 'Corvette' => 28, 'LightFrigate' => 8, 'Destroyer' => 2]),
					self::fleet('b2', 1, ['LightFighter' => 100, 'Corvette' => 28, 'LightFrigate' => 8, 'Destroyer' => 2]),
				],
				durabilityFactor: 6.0,
				maxActionsPerTurn: 25,
				rules: new \App\Modules\Ares\Domain\Model\Battle\Rules\BattleRules(morale: new \App\Modules\Ares\Domain\Model\Battle\Rules\MoraleRules(lossFactor: 1.5)),
			);

			$engine->resolveBattle($state);
			static::assertTrue($state->isOver);
		}
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
