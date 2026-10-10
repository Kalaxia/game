<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\FormationSlot;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Service\Battle\BuildFleetFormation;

class BuildFleetFormationTest extends BattleTestCase
{
	public function testShipsAreSplitIntoFullSingleTypeSquadrons(): void
	{
		// 120 light fighters fit in squadrons of 50, cruisers are alone in their squadron
		$formation = (new BuildFleetFormation())([
			[self::shipType('LightFighter'), 120],
			[self::shipType('Cruiser'), 2],
		]);

		$squadrons = array_map(fn (FormationSlot $slot) => [$slot->type->identifier, $slot->ships], $formation);

		static::assertEqualsCanonicalizing(
			[['LightFighter', 50], ['LightFighter', 50], ['LightFighter', 20], ['Cruiser', 1], ['Cruiser', 1]],
			$squadrons,
		);
	}

	public function testLineShipsStandInFrontOfArtillery(): void
	{
		$formation = (new BuildFleetFormation())([
			[self::shipType('HeavyDestroyer'), 1],
			[self::shipType('Destroyer'), 1],
		]);

		$positions = [];
		foreach ($formation as $slot) {
			// The front faces east: the further east, the higher 2q + r
			$positions[$slot->type->identifier] = 2 * $slot->offset->q + $slot->offset->r;
		}

		static::assertGreaterThan($positions['HeavyDestroyer'], $positions['Destroyer']);
	}

	public function testEverySquadronHasItsOwnCell(): void
	{
		$formation = (new BuildFleetFormation())([
			[self::shipType('Corvette'), 50],
			[self::shipType('Frigate'), 6],
		]);

		$cells = array_map(fn (FormationSlot $slot) => $slot->offset->toKey(), $formation);

		static::assertCount(count($formation), array_unique($cells));
		foreach ($formation as $slot) {
			static::assertLessThanOrEqual(2, Hex::origin()->distanceTo($slot->offset));
		}
	}

	public function testFleetTooLargeForItsFormationIsRejected(): void
	{
		$this->expectException(\DomainException::class);

		// 20 destroyers need 20 squadrons, the formation holds 19
		(new BuildFleetFormation())([[self::shipType('Destroyer'), 20]]);
	}
}
