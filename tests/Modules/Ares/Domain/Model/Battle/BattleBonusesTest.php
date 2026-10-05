<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleBonuses;
use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use PHPUnit\Framework\TestCase;

class BattleBonusesTest extends TestCase
{
	public function testNoBonusKeepsStats(): void
	{
		$bonuses = new BattleBonuses();

		static::assertSame(30.0, $bonuses->applyToAttack(ShipClass::Corvette, 30));
		static::assertSame(120.0, $bonuses->applyToDefense(ShipClass::Destroyer, 120));
	}

	public function testBonusesAddUpByClassAndStat(): void
	{
		$bonuses = (new BattleBonuses())
			->add(ShipClass::Fighter, attack: 10, speed: 10)
			->add(ShipClass::Fighter, attack: 5)
			->add(ShipClass::Destroyer, defense: -5);

		static::assertEqualsWithDelta(115.0, $bonuses->applyToAttack(ShipClass::Fighter, 100), 0.0001);
		static::assertEqualsWithDelta(110.0, $bonuses->applyToSpeed(ShipClass::Fighter, 100), 0.0001);
		static::assertEqualsWithDelta(95.0, $bonuses->applyToDefense(ShipClass::Destroyer, 100), 0.0001);
		static::assertSame(100.0, $bonuses->applyToAttack(ShipClass::Corvette, 100));
	}

	public function testMergeSumsBothBonuses(): void
	{
		$merged = (new BattleBonuses())->add(ShipClass::Frigate, attack: 10)
			->merge((new BattleBonuses())->add(ShipClass::Frigate, attack: 20, defense: 5));

		static::assertEqualsWithDelta(130.0, $merged->applyToAttack(ShipClass::Frigate, 100), 0.0001);
		static::assertEqualsWithDelta(105.0, $merged->applyToDefense(ShipClass::Frigate, 100), 0.0001);
	}
}
