<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Battle;

use App\Modules\Ares\Application\Battle\GetFactionBattleBonuses;
use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Demeter\Resource\ColorResource;
use PHPUnit\Framework\TestCase;

class GetFactionBattleBonusesTest extends TestCase
{
	private GetFactionBattleBonuses $getFactionBattleBonuses;

	protected function setUp(): void
	{
		$this->getFactionBattleBonuses = new GetFactionBattleBonuses([
			ColorResource::KALANKAR => ['bonus' => [ColorResource::DEFENSELITTLESHIPBONUS, ColorResource::PRICEBIGSHIPBONUS]],
			ColorResource::VALKAR => ['bonus' => [ColorResource::SPEEDLITTLESHIPBONUS, ColorResource::DEFENSELITTLESHIPMALUS]],
			ColorResource::FALKIRR => ['bonus' => [ColorResource::COMMERCIALROUTEINCOMEBONUS]],
		]);
	}

	public function testDefenseBonusIsAppliedOnceToEveryClass(): void
	{
		$bonuses = ($this->getFactionBattleBonuses)(ColorResource::KALANKAR);

		foreach (ShipClass::cases() as $class) {
			static::assertEqualsWithDelta(105.0, $bonuses->applyToDefense($class, 100), 0.0001, $class->name);
		}
	}

	public function testSpeedBonusAndDefenseMalus(): void
	{
		$bonuses = ($this->getFactionBattleBonuses)(ColorResource::VALKAR);

		static::assertEqualsWithDelta(110.0, $bonuses->applyToSpeed(ShipClass::Fighter, 100), 0.0001);
		static::assertEqualsWithDelta(110.0, $bonuses->applyToSpeed(ShipClass::Corvette, 100), 0.0001);
		static::assertEqualsWithDelta(95.0, $bonuses->applyToDefense(ShipClass::Frigate, 100), 0.0001);
		static::assertEqualsWithDelta(95.0, $bonuses->applyToDefense(ShipClass::Destroyer, 100), 0.0001);
		static::assertSame(100.0, $bonuses->applyToDefense(ShipClass::Fighter, 100));
	}

	public function testFactionWithoutCombatBonus(): void
	{
		static::assertSame([], ($this->getFactionBattleBonuses)(ColorResource::FALKIRR)->toArray());
		static::assertSame([], ($this->getFactionBattleBonuses)(ColorResource::NO_FACTION)->toArray());
	}
}
