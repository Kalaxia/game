<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\FleetStatus;
use App\Modules\Ares\Domain\Model\Battle\Grid\ClusteredHexGrid;
use App\Modules\Ares\Domain\Model\Battle\Rules\BattleRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\CommandRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\EngagementRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\MoraleRules;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Ares\Domain\Service\Battle\Engine\UpdateMorale;

/**
 * Phase 5 rules: fleet-size maluses (D10), engagement delay (D11), morale and retreat (D12).
 */
class BattleRulesTest extends BattleTestCase
{
	public function testCommandCapacityGrowsWithOfficerLevel(): void
	{
		$rules = new CommandRules(basePev: 300, pevPerLevel: 100, malusPerExcess: 0.5, maxMalus: 0.5);

		static::assertSame(0.0, $rules->getMalus(400, 1));
		// 50% over capacity: -25%
		static::assertEqualsWithDelta(0.25, $rules->getMalus(600, 1), 0.0001);
		// A higher officer level absorbs the same fleet
		static::assertSame(0.0, $rules->getMalus(600, 3));
		static::assertSame(0.5, $rules->getMalus(10_000, 1));
	}

	public function testOversizedFleetIsDisorganised(): void
	{
		$play = function (?CommandRules $command): BattleFleet {
			$big = self::fleet('big', 0, ['Corvette' => 140, 'Destroyer' => 4], officerLevel: 1);
			$state = self::rulesBattle([$big, self::fleet('target', 1, ['Destroyer' => 10, 'Cruiser' => 4])], new BattleRules(command: $command));
			$engine = new BattleEngine();

			for ($i = 0; $i < 8 && !$state->isOver; ++$i) {
				$engine->resolveTurn($state);
			}

			return $big;
		};

		$organised = $play(null);
		$disorganised = $play(new CommandRules());

		static::assertSame(0.0, $organised->sizeMalus);
		static::assertGreaterThan(0.0, $disorganised->sizeMalus);
		static::assertLessThan($organised->damageDealt, $disorganised->damageDealt);
	}

	public function testOutnumberedAttackersDelayTheEngagement(): void
	{
		$state = self::rulesBattle(
			[self::fleet('defender', 0, ['Destroyer' => 12]), self::fleet('attacker', 1, ['Destroyer' => 3])],
			new BattleRules(engagement: new EngagementRules(defendingSide: 0, delayTurns: 5, minForceRatio: 0.5)),
		);
		$turns = self::play($state, 7);

		static::assertSame(['until_turn' => 5], self::findEvent($turns, BattleEvent::ENGAGEMENT_DELAYED)?->payload);
		static::assertSame([], self::firingTurns(array_slice($turns, 0, 5)));
		static::assertNotSame([], self::firingTurns(array_slice($turns, 5)));
		static::assertNotNull(self::findEvent($turns, BattleEvent::ENGAGEMENT_STARTED));
	}

	public function testNoDelayWhenTheDefendersAreOutnumbered(): void
	{
		$state = self::rulesBattle(
			[self::fleet('defender', 0, ['Destroyer' => 3]), self::fleet('attacker', 1, ['Destroyer' => 12])],
			new BattleRules(engagement: new EngagementRules(defendingSide: 0, delayTurns: 5)),
		);
		$turns = self::play($state, 5);

		static::assertNull(self::findEvent($turns, BattleEvent::ENGAGEMENT_DELAYED));
		static::assertNotSame([], self::firingTurns($turns));
	}

	public function testReinforcementEndsTheDelayEarly(): void
	{
		$state = self::rulesBattle(
			[
				self::fleet('defender', 0, ['Destroyer' => 12]),
				self::fleet('attacker', 1, ['Destroyer' => 3]),
				self::fleet('reinforcement', 1, ['Destroyer' => 10], arrivalTurn: 3),
			],
			new BattleRules(engagement: new EngagementRules(defendingSide: 0, delayTurns: 10)),
		);
		$turns = self::play($state, 6);

		static::assertSame(3, $state->engagedAtTurn);
		static::assertSame(3, min(self::firingTurns($turns)));
	}

	public function testDemoralisedFleetWithdrawsBehindItsAllies(): void
	{
		[$state, $weak, $ally] = $this->moraleBattle();
		$weak->morale = 25.0;

		$events = (new UpdateMorale())($state, self::currentPev($state));

		static::assertSame(FleetStatus::Withdrawn, $weak->status);
		static::assertSame(FleetStatus::Engaged, $ally->status);
		static::assertContains(BattleEvent::FLEET_WITHDREW, array_map(fn (BattleEvent $event) => $event->type, $events));
		static::assertFalse($weak->canFire());
	}

	public function testWithdrawnFleetRemobilises(): void
	{
		[$state, $weak] = $this->moraleBattle();
		$weak->status = FleetStatus::Withdrawn;
		$weak->morale = 49.5;

		// No losses this turn and an ally nearby: morale recovers above the threshold
		(new UpdateMorale())($state, self::currentPev($state));

		static::assertSame(FleetStatus::Engaged, $weak->status);
	}

	public function testFleetAloneFleesThenLeavesTheBattle(): void
	{
		[$state, $weak, $ally] = $this->moraleBattle();
		$ally->status = FleetStatus::Fled;
		$weak->morale = 25.0;
		$ships = $weak->getPev();
		$updateMorale = new UpdateMorale();

		$updateMorale($state, self::currentPev($state));
		static::assertSame(FleetStatus::Fleeing, $weak->status);

		// Still exposed for two turns
		++$state->turn;
		$updateMorale($state, self::currentPev($state));
		static::assertSame(FleetStatus::Fleeing, $weak->status);

		++$state->turn;
		$events = $updateMorale($state, self::currentPev($state));
		static::assertSame(FleetStatus::Fled, $weak->status);
		static::assertContains(BattleEvent::FLEET_FLED, array_map(fn (BattleEvent $event) => $event->type, $events));

		// The fleet left with its ships: it is neither destroyed nor on the battlefield
		static::assertSame($ships, $weak->getPev());
		static::assertFalse($weak->isDestroyed());
		static::assertNotContains($weak, $state->getActiveFleets());
		static::assertNotContains($weak->side, $state->getRemainingSides());
	}

	public function testLossesLowerMorale(): void
	{
		[$state, $weak] = $this->moraleBattle();
		$pevBefore = self::currentPev($state);
		$squadron = $weak->getActiveSquadrons()[0];
		$squadron->ships = intdiv($squadron->ships, 2);

		(new UpdateMorale())($state, $pevBefore);

		static::assertLessThan(100.0, $weak->morale);
	}

	public function testInflictingLossesRaisesMorale(): void
	{
		[$state, $weak] = $this->moraleBattle();
		$weak->morale = 40.0;
		$pevDestroyedBefore = [$weak->identifier => $weak->pevDestroyed];
		// The fleet destroyed enemy ships worth half its own PEV this turn
		$weak->pevDestroyed += intdiv($weak->getFormationPev(), 2);

		(new UpdateMorale())($state, self::currentPev($state), $pevDestroyedBefore);

		// +50% of its PEV × 0.5 per percent = +25, plus the recovery of a turn without losses
		static::assertGreaterThanOrEqual(65.0, $weak->morale);
	}

	/**
	 * A deployed battle with a weak fleet and its ally on side 0, against side 1.
	 *
	 * @return array{BattleState, BattleFleet, BattleFleet}
	 */
	private function moraleBattle(): array
	{
		$weak = self::fleet('weak', 0, ['Corvette' => 14]);
		$ally = self::fleet('ally', 0, ['Destroyer' => 4]);
		$state = self::rulesBattle([$weak, self::fleet('enemy', 1, ['Destroyer' => 4]), $ally], new BattleRules(morale: new MoraleRules()));
		(new BattleEngine())->resolveTurn($state);
		$weak->morale = 100.0;
		$weak->status = FleetStatus::Engaged;

		return [$state, $weak, $ally];
	}

	/**
	 * @param list<BattleFleet> $fleets
	 */
	private static function rulesBattle(array $fleets, BattleRules $rules): BattleState
	{
		return new BattleState(grid: new ClusteredHexGrid(4), battlefieldSize: 5, seed: 42, fleets: $fleets, durabilityFactor: 5.0, rules: $rules);
	}

	/**
	 * @return list<TurnResult>
	 */
	private static function play(BattleState $state, int $turns): array
	{
		$engine = new BattleEngine();
		$results = [];

		for ($i = 0; $i < $turns && !$state->isOver; ++$i) {
			$results[] = $engine->resolveTurn($state);
		}

		return $results;
	}

	/**
	 * @param list<TurnResult> $turns
	 *
	 * @return list<int> turns in which at least one squadron fired
	 */
	private static function firingTurns(array $turns): array
	{
		return array_values(array_map(
			fn (TurnResult $turn) => $turn->turn,
			array_filter($turns, fn (TurnResult $turn) => [] !== array_filter($turn->events, fn (BattleEvent $event) => BattleEvent::SQUADRON_FIRED === $event->type)),
		));
	}

	/**
	 * @param list<TurnResult> $turns
	 */
	private static function findEvent(array $turns, string $type): ?BattleEvent
	{
		foreach ($turns as $turn) {
			foreach ($turn->events as $event) {
				if ($type === $event->type) {
					return $event;
				}
			}
		}

		return null;
	}

	/**
	 * @return array<string, int>
	 */
	private static function currentPev(BattleState $state): array
	{
		$pev = [];
		foreach ($state->getActiveFleets() as $fleet) {
			$pev[$fleet->identifier] = $fleet->getPev();
		}

		return $pev;
	}
}
