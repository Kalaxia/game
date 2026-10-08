<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\Report\BattleReport;
use App\Modules\Ares\Domain\Model\Battle\Report\SquadronReport;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Ares\Domain\Service\Battle\BuildBattleReport;
use App\Modules\Ares\Domain\Service\Battle\ComputeBattleExperience;

class BuildBattleReportTest extends BattleTestCase
{
	public function testFiguresAreConsistentWithTheBattle(): void
	{
		$a = self::fleet('a', 0, ['Corvette' => 28, 'Frigate' => 4]);
		$b = self::fleet('b', 1, ['LightFighter' => 50, 'Destroyer' => 2]);
		$state = self::battle([$a, $b], durabilityFactor: 3.0, maxActionsPerTurn: 10);
		$turns = (new BattleEngine())->resolveBattle($state);
		$experience = (new ComputeBattleExperience())($state);

		$report = (new BuildBattleReport())($state, $turns, $experience);
		$fleets = array_column($report->fleets, null, 'fleet');

		static::assertSame($state->turn, $report->turns);
		static::assertSame($state->winningSide, $report->winningSide);

		// Damage counted per squadron matches the fleets' own counters
		static::assertSame($a->damageDealt, $fleets['a']['damage_dealt']);
		static::assertSame($a->damageTaken, $fleets['a']['damage_taken']);
		static::assertSame($b->damageDealt, $fleets['b']['damage_dealt']);
		static::assertSame($experience['a'], $fleets['a']['experience']);

		// Remaining ships rebuilt from the events match the battle state
		foreach ($state->fleets as $fleet) {
			foreach ($fleet->squadrons as $squadron) {
				$squadronReport = array_find($report->squadrons, fn (SquadronReport $report) => $report->squadron === $squadron->identifier);
				static::assertSame($squadron->ships, $squadronReport->remainingShips, $squadron->identifier);
			}
		}

		// Ships destroyed by one side are the ships lost by the other
		static::assertSame(array_sum($fleets['a']['kills_by_type']), $report->sides[1]['ships_lost']);
		static::assertSame(array_sum($fleets['b']['kills_by_type']), $report->sides[0]['ships_lost']);
		static::assertSame($fleets['b']['initial_pev'] - $fleets['b']['remaining_pev'], $report->sides[1]['lost_pev']);
	}

	public function testKeyMomentsAreListedInTurnOrder(): void
	{
		$state = self::battle([
			self::fleet('a', 0, ['Destroyer' => 6]),
			self::fleet('b', 1, ['Destroyer' => 4]),
			self::fleet('c', 0, ['Destroyer' => 3], arrivalTurn: 4),
		], durabilityFactor: 3.0, maxActionsPerTurn: 10);
		$turns = (new BattleEngine())->resolveBattle($state);

		$report = (new BuildBattleReport())($state, $turns);
		$types = array_column($report->highlights, 'type');

		static::assertContains(BattleReport::HIGHLIGHT_FIRST_BLOOD, $types);
		static::assertContains(BattleReport::HIGHLIGHT_DEADLIEST_ACTION, $types);
		static::assertContains(BattleReport::HIGHLIGHT_MOST_VALUABLE_SQUADRON, $types);
		static::assertContains(BattleReport::HIGHLIGHT_FLEET_DESTROYED, $types);

		$reinforcement = array_find($report->highlights, fn (array $highlight) => BattleReport::HIGHLIGHT_REINFORCEMENT === $highlight['type']);
		static::assertSame(['type' => BattleReport::HIGHLIGHT_REINFORCEMENT, 'turn' => 4, 'fleet' => 'c'], $reinforcement);

		$highlightTurns = array_column($report->highlights, 'turn');
		$sortedTurns = $highlightTurns;
		sort($sortedTurns);
		static::assertSame($sortedTurns, $highlightTurns);
	}

	public function testTurningPointWhenTheEventualWinnerWasBehind(): void
	{
		// b starts outnumbered, then a big reinforcement turns the battle around
		$state = self::battle([
			self::fleet('a', 0, ['Destroyer' => 8]),
			self::fleet('b', 1, ['Destroyer' => 4]),
			self::fleet('c', 1, ['Destroyer' => 10, 'Cruiser' => 4], arrivalTurn: 3),
		], durabilityFactor: 3.0, maxActionsPerTurn: 10);
		$turns = (new BattleEngine())->resolveBattle($state);

		$report = (new BuildBattleReport())($state, $turns);
		$turningPoint = array_find($report->highlights, fn (array $highlight) => BattleReport::HIGHLIGHT_TURNING_POINT === $highlight['type']);

		static::assertSame(1, $state->winningSide);
		static::assertNotNull($turningPoint);
		static::assertSame(1, $turningPoint['side']);
		static::assertGreaterThanOrEqual(3, $turningPoint['turn']);
	}

	public function testNoTurningPointWhenTheWinnerAlwaysLed(): void
	{
		$state = self::battle([self::fleet('a', 0, ['HeavyCorvette' => 44]), self::fleet('b', 1, ['LightFighter' => 10])]);
		$turns = (new BattleEngine())->resolveBattle($state);

		$report = (new BuildBattleReport())($state, $turns);

		static::assertNotContains(BattleReport::HIGHLIGHT_TURNING_POINT, array_column($report->highlights, 'type'));
	}
}
