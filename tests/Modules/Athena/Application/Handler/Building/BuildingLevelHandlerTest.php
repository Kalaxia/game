<?php

namespace App\Tests\Modules\Athena\Application\Handler\Building;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Gaia\Domain\Enum\PlaceType;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Zeus\Model\Player;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class BuildingLevelHandlerTest extends TestCase
{
	private BuildingLevelHandler $buildingLevelHandler;

	public function setUp(): void
	{
		$this->buildingLevelHandler = new BuildingLevelHandler();
	}

	public function testIncreaseBuildingLevel(): void
	{
		$planet = static::generatePlanet();

		$this->buildingLevelHandler->increaseBuildingLevel($planet, PlanetResource::GENERATOR);
		$this->buildingLevelHandler->increaseBuildingLevel($planet, PlanetResource::SPATIOPORT);
		$this->buildingLevelHandler->increaseBuildingLevel($planet, PlanetResource::REFINERY);

		static::assertEquals(4, $planet->levelGenerator);
		static::assertEquals(1, $planet->levelSpatioport);
		static::assertEquals(3, $planet->levelRefinery);

		$this->buildingLevelHandler->increaseBuildingLevel($planet, PlanetResource::GENERATOR);

		static::assertEquals(5, $planet->levelGenerator);
	}

	public function testDecreaseBuildingLevel(): void
	{
		$planet = static::generatePlanet();

		$this->buildingLevelHandler->decreaseBuildingLevel($planet, PlanetResource::GENERATOR);
		$this->buildingLevelHandler->decreaseBuildingLevel($planet, PlanetResource::SPATIOPORT);
		$this->buildingLevelHandler->decreaseBuildingLevel($planet, PlanetResource::REFINERY);

		static::assertEquals(2, $planet->levelGenerator);
		static::assertEquals(0, $planet->levelSpatioport);
		static::assertEquals(1, $planet->levelRefinery);

		$this->buildingLevelHandler->decreaseBuildingLevel($planet, PlanetResource::GENERATOR);

		static::assertEquals(1, $planet->levelGenerator);
	}

	public function testGetBuildingLevel(): void
	{
		$planet = $this->generatePlanet();

		static::assertEquals(
			$planet->levelGenerator,
			$this->buildingLevelHandler->getBuildingLevel($planet, PlanetResource::GENERATOR),
		);

		static::assertEquals(
			$planet->levelRefinery,
			$this->buildingLevelHandler->getBuildingLevel($planet, PlanetResource::REFINERY),
		);

		static::assertEquals(
			$planet->levelStorage,
			$this->buildingLevelHandler->getBuildingLevel($planet, PlanetResource::STORAGE),
		);

		static::assertEquals(
			$planet->levelDock1,
			$this->buildingLevelHandler->getBuildingLevel($planet, PlanetResource::DOCK1),
		);

		static::assertEquals(
			$planet->levelCommercialPlateforme,
			$this->buildingLevelHandler->getBuildingLevel($planet, PlanetResource::COMMERCIAL_PLATEFORME),
		);
	}

	/**
	 * @param list<BuildingQueue> $buildingQueues
	 *
	 * @dataProvider provideData
	 */
	public function testGetBuildingRealLevel(
		Planet $planet,
		array  $buildingQueues,
		int    $buildingIdentifier,
		int    $expectedRealLevel,
	): void {
		$realLevel = $this->buildingLevelHandler->getBuildingRealLevel(
			$planet,
			$buildingIdentifier,
			$buildingQueues,
		);

		static::assertEquals($realLevel, $expectedRealLevel);
	}

	public function testGetInvalidBuildingLevel(): void
	{
		$planet = static::generatePlanet();

		static::expectException(\LogicException::class);
		static::expectExceptionMessage('Building identifier 99 is not valid');

		$this->buildingLevelHandler->getBuildingLevel($planet, 99);
	}

	public function testGetInvalidBuildingRealLevel(): void
	{
		$planet = static::generatePlanet();
		$buildingQueues = static::generateBuildingQueues($planet);

		static::expectException(\LogicException::class);
		static::expectExceptionMessage('Building identifier 99 is not valid');

		$this->buildingLevelHandler->getBuildingRealLevel($planet, 99, $buildingQueues);
	}

	/**
	 * @return \Generator{0: Planet, 1: list<BuildingQueue>, 2: int, 3: int}
	 */
	public static function provideData(): \Generator
	{
		$planet = static::generatePlanet();
		$buildingQueues = static::generateBuildingQueues($planet);

		yield [
			$planet,
			$buildingQueues,
			PlanetResource::GENERATOR,
			7,
		];

		yield [
			$planet,
			$buildingQueues,
			PlanetResource::REFINERY,
			4,
		];

		yield [
			$planet,
			$buildingQueues,
			PlanetResource::COMMERCIAL_PLATEFORME,
			1,
		];

		yield [
			$planet,
			$buildingQueues,
			PlanetResource::DOCK1,
			2,
		];

		yield [
			$planet,
			$buildingQueues,
			PlanetResource::STORAGE,
			5,
		];

		yield [
			$planet,
			$buildingQueues,
			PlanetResource::DOCK2,
			0,
		];
	}

	private static function generatePlanet(): Planet
	{
		return new Planet(
			id: Uuid::v4(),
			place: new Planet(
				id: Uuid::v4(),
				base: null,
				system: new System(
					id: Uuid::v4(),
					sector: new Sector(),
					faction: null,
					xPosition: 10,
					yPosition: 20,
					typeOfSystem: 0,
				),
				typeOfPlace: PlaceType::Planet,
				position: 1,
				population: 100,
				coefResources: 60,
				coefHistory: 20,
				resources: 20000,
				danger: 40,
				maxDanger: 60,
				updatedAt: new \DateTimeImmutable(),
			),
			player: new Player(),
			name: 'My wonderful base',
			levelGenerator: 3,
			levelRefinery: 2,
			levelCommercialPlateforme: 0,
			levelStorage: 5,
		);
	}

	/**
	 * @return list<BuildingQueue>
	 */
	private static function generateBuildingQueues(Planet $planet): array
	{
		return [
			static::generateBuildingQueue($planet, PlanetResource::GENERATOR, 6),
			static::generateBuildingQueue($planet, PlanetResource::GENERATOR, 5),
			static::generateBuildingQueue($planet, PlanetResource::REFINERY, 4),
			static::generateBuildingQueue($planet, PlanetResource::GENERATOR, 7),
			static::generateBuildingQueue($planet, PlanetResource::REFINERY, 3),
			static::generateBuildingQueue($planet, PlanetResource::COMMERCIAL_PLATEFORME, 1),
			static::generateBuildingQueue($planet, PlanetResource::DOCK1, 1),
			static::generateBuildingQueue($planet, PlanetResource::DOCK1, 2),
		];
	}

	private static function generateBuildingQueue(
		Planet $planet,
		int    $buildingIdentifier,
		int    $targetLevel,
	): BuildingQueue {
		return new BuildingQueue(
			id: Uuid::v4(),
			base: $planet,
			buildingNumber: $buildingIdentifier,
			targetLevel: $targetLevel,
			startedAt: new \DateTimeImmutable(),
			endedAt: new \DateTimeImmutable(),
		);
	}
}
