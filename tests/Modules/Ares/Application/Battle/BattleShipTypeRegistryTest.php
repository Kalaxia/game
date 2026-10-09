<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Battle;

use App\Modules\Ares\Application\Battle\BattleShipTypeRegistry;
use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Infrastructure\DependencyInjection\BattleShipTypeConfiguration;
use App\Modules\Ares\Infrastructure\DependencyInjection\ShipCategoryConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class BattleShipTypeRegistryTest extends TestCase
{
	public function testProjectConfigurationIsValidAndComplete(): void
	{
		$registry = $this->createRegistryFromProjectConfiguration();
		$types = $registry->all();

		$linkedCategories = array_filter(array_map(fn ($type) => $type->shipCategory, $types));
		static::assertCount(count(ShipCategory::cases()), $linkedCategories, 'Every ship category needs a battle ship type');
	}

	public function testLinkedTypeTakesBaseStatsFromShipCategory(): void
	{
		$destroyer = $this->createRegistryFromProjectConfiguration()->get('Destroyer');

		static::assertSame(ShipCategory::Destroyer, $destroyer->shipCategory);
		static::assertSame(ShipClass::Destroyer, $destroyer->class);
		static::assertSame(75, $destroyer->pev);
		static::assertSame(1200, $destroyer->life);
		static::assertSame(1, $destroyer->squadronSize);
		static::assertSame([35, 35, 35, 35, 25, 10, 10], $destroyer->attack);
	}

	public function testTypeOfAShipCategory(): void
	{
		$registry = $this->createRegistryFromProjectConfiguration();

		static::assertSame('HeavyCorvette', $registry->getByCategory(ShipCategory::HeavyCorvette)->identifier);
		static::assertSame(ShipCategory::HeavyCorvette, $registry->getByCategory(ShipCategory::HeavyCorvette)->shipCategory);
	}

	public function testTypeWithoutCategoryMustDefineBaseStats(): void
	{
		$this->expectException(InvalidConfigurationException::class);

		(new Processor())->processConfiguration(new BattleShipTypeConfiguration(), [[
			'Incomplete' => ['role' => 'line', 'squadron_size' => 1, 'movement' => 1, 'initiative' => 1, 'range' => 1],
		]]);
	}

	private function createRegistryFromProjectConfiguration(): BattleShipTypeRegistry
	{
		$projectDir = dirname(__DIR__, 5);
		$processor = new Processor();

		$shipCategories = $processor->processConfiguration(
			new ShipCategoryConfiguration(),
			[Yaml::parseFile($projectDir.'/config/game/ship_categories.yaml', Yaml::PARSE_CONSTANT)['ship_categories']],
		);
		$battleShipTypes = $processor->processConfiguration(
			new BattleShipTypeConfiguration(),
			[Yaml::parseFile($projectDir.'/config/game/battle_ship_types.yaml', Yaml::PARSE_CONSTANT)['battle_ship_types']],
		);

		return new BattleShipTypeRegistry($battleShipTypes, $shipCategories);
	}
}
