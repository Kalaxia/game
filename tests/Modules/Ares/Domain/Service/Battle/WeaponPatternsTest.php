<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\Grid\ClusteredHexGrid;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Service\Battle\Engine\MoveSquadrons;
use App\Modules\Ares\Domain\Service\Battle\Engine\ResolveFire;
use App\Modules\Ares\Domain\Service\Battle\Engine\SelectTarget;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Weapon patterns (Q12): minimum range, area damage and split fire.
 */
class WeaponPatternsTest extends BattleTestCase
{
	public function testArtilleryCannotHitAdjacentTargets(): void
	{
		$state = self::state();
		$frigate = self::place($state, 'a', 0, 'Frigate', 1, new Hex(10, 10));
		$adjacent = self::place($state, 'b', 1, 'Destroyer', 1, new Hex(11, 10));
		$inRange = self::place($state, 'c', 1, 'Destroyer', 1, new Hex(10, 12));

		$candidates = (new SelectTarget())->getCandidates($state, $frigate, true);

		static::assertNotContains($adjacent, $candidates);
		static::assertContains($inRange, $candidates);
	}

	public function testArtilleryStepsBackToFire(): void
	{
		$state = self::state();
		$frigate = self::place($state, 'a', 0, 'Frigate', 1, new Hex(10, 10));
		$enemy = self::place($state, 'b', 1, 'Destroyer', 1, new Hex(11, 10));

		(new MoveSquadrons())($state, [$frigate], new Randomizer(new Mt19937(1)));

		$distance = $frigate->cell->distanceTo($enemy->cell);
		static::assertGreaterThanOrEqual(2, $distance);
		static::assertLessThanOrEqual(3, $distance);
	}

	public function testAreaDamageHitsSquadronsNextToTheTarget(): void
	{
		$state = self::state();
		$missiles = self::place($state, 'a', 0, 'HeavyDestroyer', 1, new Hex(10, 10));
		$target = self::place($state, 'b', 1, 'Corvette', 14, new Hex(13, 10));
		$enemyNeighbour = self::place($state, 'c', 1, 'Corvette', 14, new Hex(14, 10));
		$allyNeighbour = self::place($state, 'd', 0, 'Corvette', 14, new Hex(12, 10));

		$events = (new ResolveFire())($state, [$missiles], new Randomizer(new Mt19937(3)));
		$splashed = array_column(array_map(
			fn (BattleEvent $event) => $event->payload,
			array_filter($events, fn (BattleEvent $event) => BattleEvent::SQUADRON_SPLASHED === $event->type),
		), null, 'target');

		static::assertArrayHasKey($enemyNeighbour->identifier, $splashed);
		static::assertArrayHasKey($allyNeighbour->identifier, $splashed, 'Area damage hits allies too');
		static::assertGreaterThan(0, $allyNeighbour->getFleet()->damageTaken);

		// Damage on allies is not counted as damage dealt by the shooter
		$enemyDamage = array_sum(array_map(
			fn (BattleEvent $event) => $event->payload['damage'],
			array_filter($events, fn (BattleEvent $event) => in_array($event->type, [BattleEvent::SQUADRON_FIRED, BattleEvent::SQUADRON_SPLASHED], true) && $event->payload['target'] !== $allyNeighbour->identifier),
		));
		static::assertSame($enemyDamage, $missiles->getFleet()->damageDealt);
		static::assertGreaterThan(0, $target->getFleet()->damageTaken);
	}

	public function testSplitFireSharesShotsBetweenTwoTargets(): void
	{
		$state = self::state();
		$fighters = self::place($state, 'a', 0, 'HeavyFighter', 33, new Hex(10, 10));
		self::place($state, 'b', 1, 'Destroyer', 1, new Hex(11, 10));
		self::place($state, 'c', 1, 'Destroyer', 1, new Hex(10, 11));

		$events = (new ResolveFire())($state, [$fighters], new Randomizer(new Mt19937(5)));
		$fired = array_values(array_filter($events, fn (BattleEvent $event) => BattleEvent::SQUADRON_FIRED === $event->type));

		static::assertCount(2, array_unique(array_map(fn (BattleEvent $event) => $event->payload['target'], $fired)));
		// 33 heavy fighters fire 2 shots each: 33 shots for each target
		static::assertSame(33, $fired[0]->payload['shots']);
		static::assertSame(33, $fired[1]->payload['shots']);
	}

	private static function state(): BattleState
	{
		return new BattleState(grid: new ClusteredHexGrid(4), battlefieldSize: 5, seed: 1);
	}

	/**
	 * Places a single squadron in its own fleet, deployed on the cluster of its cell.
	 */
	private static function place(BattleState $state, string $fleetId, int $side, string $type, int $ships, Hex $cell): BattleSquadron
	{
		$fleet = new BattleFleet(identifier: $fleetId, name: $fleetId, faction: $side + 1, side: $side, officerLevel: 1, formation: []);
		$fleet->cluster = $state->grid->clusterOf($cell);
		$squadron = new BattleSquadron(sprintf('%s-0', $fleetId), self::shipType($type), $ships, $cell, $fleet);
		$fleet->squadrons[] = $squadron;
		$state->addFleet($fleet);
		$state->placeSquadron($squadron, $cell);

		return $squadron;
	}
}
