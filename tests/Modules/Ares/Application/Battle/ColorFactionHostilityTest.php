<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Battle;

use App\Modules\Ares\Application\Battle\ColorFactionHostility;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ColorFactionHostilityTest extends TestCase
{
	public function testSameFactionIsNeverHostile(): void
	{
		static::assertFalse($this->createHostility([])->areEnemies(ColorResource::KALANKAR, ColorResource::KALANKAR));
	}

	public function testRebelsAreEnemiesOfEveryone(): void
	{
		static::assertTrue($this->createHostility([])->areEnemies(ColorResource::NO_FACTION, ColorResource::KALANKAR));
		static::assertTrue($this->createHostility([])->areEnemies(ColorResource::VALKAR, ColorResource::NO_FACTION));
	}

	public function testOneSidedEnemyDeclarationIsEnough(): void
	{
		$hostility = $this->createHostility([
			ColorResource::KALANKAR => [ColorResource::VALKAR => Color::ENEMY],
			ColorResource::VALKAR => [ColorResource::KALANKAR => Color::NEUTRAL],
		]);

		static::assertTrue($hostility->areEnemies(ColorResource::KALANKAR, ColorResource::VALKAR));
		static::assertTrue($hostility->areEnemies(ColorResource::VALKAR, ColorResource::KALANKAR));
	}

	public function testPeaceAllianceAndNeutralityAreNotHostile(): void
	{
		$hostility = $this->createHostility([
			ColorResource::KALANKAR => [ColorResource::VALKAR => Color::ALLY, ColorResource::FALKIRR => Color::PEACE],
			ColorResource::VALKAR => [ColorResource::KALANKAR => Color::ALLY],
			ColorResource::FALKIRR => [],
		]);

		static::assertFalse($hostility->areEnemies(ColorResource::KALANKAR, ColorResource::VALKAR));
		static::assertFalse($hostility->areEnemies(ColorResource::KALANKAR, ColorResource::FALKIRR));
	}

	/**
	 * @param array<int, array<int, int>> $relations relations of each faction, by faction identifier
	 */
	private function createHostility(array $relations): ColorFactionHostility
	{
		$colorRepository = static::createStub(ColorRepositoryInterface::class);
		$colorRepository
			->method('getOneByIdentifier')
			->willReturnCallback(fn (int $identifier) => new Color(
				id: Uuid::v4(),
				identifier: $identifier,
				relations: $relations[$identifier] ?? [],
			));

		return new ColorFactionHostility($colorRepository);
	}
}
