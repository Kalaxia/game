<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\FormationSlot;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Service\Battle\BuildFleetFormation;
use App\Modules\Ares\Domain\Service\Battle\ValidateFormation;

class ValidateFormationTest extends BattleTestCase
{
	public function testValidPlayerFormation(): void
	{
		$formation = [
			new FormationSlot(self::shipType('Corvette'), 14, new Hex(2, 0)),
			new FormationSlot(self::shipType('Cruiser'), 1, Hex::origin()),
			new FormationSlot(self::shipType('Bomber'), 10, new Hex(-1, 2)),
		];

		static::assertSame([], (new ValidateFormation())($formation));
	}

	public function testAutomaticFormationIsValid(): void
	{
		$formation = (new BuildFleetFormation())([[self::shipType('LightFighter'), 120], [self::shipType('Destroyer'), 5]]);

		static::assertSame([], (new ValidateFormation())($formation));
	}

	public function testCellOutsideTheGridIsRejected(): void
	{
		$violations = (new ValidateFormation())([new FormationSlot(self::shipType('Corvette'), 5, new Hex(3, 0))]);

		static::assertCount(1, $violations);
		static::assertStringContainsString('outside', (string) $violations[0]);
	}

	public function testTwoSquadronsOnTheSameCellAreRejected(): void
	{
		$violations = (new ValidateFormation())([
			new FormationSlot(self::shipType('Corvette'), 5, new Hex(1, 0)),
			new FormationSlot(self::shipType('Frigate'), 1, new Hex(1, 0)),
		]);

		static::assertCount(1, $violations);
		static::assertStringContainsString('more than one squadron', (string) $violations[0]);
	}

	public function testSquadronLargerThanItsTypeAllowsIsRejected(): void
	{
		// A cruiser squadron holds a single ship
		$violations = (new ValidateFormation())([new FormationSlot(self::shipType('Cruiser'), 2, Hex::origin())]);

		static::assertCount(1, $violations);
		static::assertStringContainsString('1 to 1 ships', (string) $violations[0]);
	}

	public function testEmptyFormationIsRejected(): void
	{
		static::assertNotSame([], (new ValidateFormation())([]));
	}
}
