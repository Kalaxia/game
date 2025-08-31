<?php

namespace App\Tests\Modules\Athena\Domain\Service\Base\Building;

use App\Modules\Athena\Domain\Service\Base\Building\BuildingDataHandler;
use App\Modules\Gaia\Resource\PlanetResource;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BuildingDataHandlerTest extends KernelTestCase
{
	#[DataProvider('provideTestDataForBuildingCost')]
	public function testGetBuildingTimeCost(int $identifier, int $level, array $costs): void
	{
		static::bootKernel();
		/** @var BuildingDataHandler $handler */
		$handler = static::getContainer()->get(BuildingDataHandler::class);

		static::assertSame($handler->getBuildingTimeCost($identifier, $level), $costs['time']);
	}

	#[DataProvider('provideTestDataForBuildingCost')]
	public function testGetBuildingResourceCost(int $identifier, int $level, array $costs): void
	{
		static::bootKernel();
		/** @var BuildingDataHandler $handler */
		$handler = static::getContainer()->get(BuildingDataHandler::class);

		static::assertSame($handler->getBuildingResourceCost($identifier, $level), $costs['resources']);
	}

	/**
	 * @return Generator<array{0: int, 1: int, 2: array{time: int, resources: int}}>
	 */
	public static function provideTestDataForBuildingCost(): Generator
	{
		yield [
			PlanetResource::GENERATOR,
			2,
			[
				'time' => 28,
				'resources' => 137,
			],
		];

		yield [
			PlanetResource::REFINERY,
			14,
			[
				'time' => 800,
				'resources' => 4130,
			],
		];

		yield [
			PlanetResource::SPATIOPORT,
			10,
			[
				'time' => 2066,
				'resources' => 27000,
			],
		];
	}
}
