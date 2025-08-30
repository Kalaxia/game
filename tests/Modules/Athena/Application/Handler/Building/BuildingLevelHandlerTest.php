<?php

namespace App\Tests\Modules\Athena\Application\Handler\Building;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Entity\System;
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
		$orbitalBase = static::generateOrbitalBase();

		$this->buildingLevelHandler->increaseBuildingLevel($orbitalBase, OrbitalBaseResource::GENERATOR);
		$this->buildingLevelHandler->increaseBuildingLevel($orbitalBase, OrbitalBaseResource::SPATIOPORT);
		$this->buildingLevelHandler->increaseBuildingLevel($orbitalBase, OrbitalBaseResource::REFINERY);

		static::assertEquals(4, $orbitalBase->levelGenerator);
		static::assertEquals(1, $orbitalBase->levelSpatioport);
		static::assertEquals(3, $orbitalBase->levelRefinery);

		$this->buildingLevelHandler->increaseBuildingLevel($orbitalBase, OrbitalBaseResource::GENERATOR);

		static::assertEquals(5, $orbitalBase->levelGenerator);
	}

	public function testDecreaseBuildingLevel(): void
	{
		$orbitalBase = static::generateOrbitalBase();

		$this->buildingLevelHandler->decreaseBuildingLevel($orbitalBase, OrbitalBaseResource::GENERATOR);
		$this->buildingLevelHandler->decreaseBuildingLevel($orbitalBase, OrbitalBaseResource::SPATIOPORT);
		$this->buildingLevelHandler->decreaseBuildingLevel($orbitalBase, OrbitalBaseResource::REFINERY);

		static::assertEquals(2, $orbitalBase->levelGenerator);
		static::assertEquals(0, $orbitalBase->levelSpatioport);
		static::assertEquals(1, $orbitalBase->levelRefinery);

		$this->buildingLevelHandler->decreaseBuildingLevel($orbitalBase, OrbitalBaseResource::GENERATOR);

		static::assertEquals(1, $orbitalBase->levelGenerator);
	}

	public function testGetBuildingLevel(): void
	{
		$orbitalBase = $this->generateOrbitalBase();

		static::assertEquals(
			$orbitalBase->levelGenerator,
			$this->buildingLevelHandler->getBuildingLevel($orbitalBase, OrbitalBaseResource::GENERATOR),
		);

		static::assertEquals(
			$orbitalBase->levelRefinery,
			$this->buildingLevelHandler->getBuildingLevel($orbitalBase, OrbitalBaseResource::REFINERY),
		);

		static::assertEquals(
			$orbitalBase->levelStorage,
			$this->buildingLevelHandler->getBuildingLevel($orbitalBase, OrbitalBaseResource::STORAGE),
		);

		static::assertEquals(
			$orbitalBase->levelDock1,
			$this->buildingLevelHandler->getBuildingLevel($orbitalBase, OrbitalBaseResource::DOCK1),
		);

		static::assertEquals(
			$orbitalBase->levelCommercialPlateforme,
			$this->buildingLevelHandler->getBuildingLevel($orbitalBase, OrbitalBaseResource::COMMERCIAL_PLATEFORME),
		);
	}

	/**
	 * @param list<BuildingQueue> $buildingQueues
	 *
	 * @dataProvider provideData
	 */
	public function testGetBuildingRealLevel(
		OrbitalBase $orbitalBase,
		array $buildingQueues,
		int $buildingIdentifier,
		int $expectedRealLevel,
	): void {
		$realLevel = $this->buildingLevelHandler->getBuildingRealLevel(
			$orbitalBase,
			$buildingIdentifier,
			$buildingQueues,
		);

		static::assertEquals($realLevel, $expectedRealLevel);
	}

	public function testGetInvalidBuildingLevel(): void
	{
		$orbitalBase = static::generateOrbitalBase();

		static::expectException(\LogicException::class);
		static::expectExceptionMessage('Building identifier 99 is not valid');

		$this->buildingLevelHandler->getBuildingLevel($orbitalBase, 99);
	}

	public function testGetInvalidBuildingRealLevel(): void
	{
		$orbitalBase = static::generateOrbitalBase();
		$buildingQueues = static::generateBuildingQueues($orbitalBase);

		static::expectException(\LogicException::class);
		static::expectExceptionMessage('Building identifier 99 is not valid');

		$this->buildingLevelHandler->getBuildingRealLevel($orbitalBase, 99, $buildingQueues);
	}

	/**
	 * @return \Generator{0: OrbitalBase, 1: list<BuildingQueue>, 2: int, 3: int}
	 */
	public static function provideData(): \Generator
	{
		$orbitalBase = static::generateOrbitalBase();
		$buildingQueues = static::generateBuildingQueues($orbitalBase);

		yield [
			$orbitalBase,
			$buildingQueues,
			OrbitalBaseResource::GENERATOR,
			7,
		];

		yield [
			$orbitalBase,
			$buildingQueues,
			OrbitalBaseResource::REFINERY,
			4,
		];

		yield [
			$orbitalBase,
			$buildingQueues,
			OrbitalBaseResource::COMMERCIAL_PLATEFORME,
			1,
		];

		yield [
			$orbitalBase,
			$buildingQueues,
			OrbitalBaseResource::DOCK1,
			2,
		];

		yield [
			$orbitalBase,
			$buildingQueues,
			OrbitalBaseResource::STORAGE,
			5,
		];

		yield [
			$orbitalBase,
			$buildingQueues,
			OrbitalBaseResource::DOCK2,
			0,
		];
	}

	private static function generateOrbitalBase(): OrbitalBase
	{
		return new OrbitalBase(
			id: Uuid::v4(),
			place: new Place(
				id: Uuid::v4(),
				player: new Player(),
				base: null,
				system: new System(
					id: Uuid::v4(),
					sector: null,
					faction: null,
					xPosition: 10,
					yPosition: 20,
					typeOfSystem: 0,
				),
				typeOfPlace: Place::TERRESTRIAL,
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
	private static function generateBuildingQueues(OrbitalBase $orbitalBase): array
	{
		return [
			static::generateBuildingQueue($orbitalBase, OrbitalBaseResource::GENERATOR, 6),
			static::generateBuildingQueue($orbitalBase, OrbitalBaseResource::GENERATOR, 5),
			static::generateBuildingQueue($orbitalBase, OrbitalBaseResource::REFINERY, 4),
			static::generateBuildingQueue($orbitalBase, OrbitalBaseResource::GENERATOR, 7),
			static::generateBuildingQueue($orbitalBase, OrbitalBaseResource::REFINERY, 3),
			static::generateBuildingQueue($orbitalBase, OrbitalBaseResource::COMMERCIAL_PLATEFORME, 1),
			static::generateBuildingQueue($orbitalBase, OrbitalBaseResource::DOCK1, 1),
			static::generateBuildingQueue($orbitalBase, OrbitalBaseResource::DOCK1, 2),
		];
	}

	private static function generateBuildingQueue(
		OrbitalBase $orbitalBase,
		int $buildingIdentifier,
		int $targetLevel,
	): BuildingQueue {
		return new BuildingQueue(
			id: Uuid::v4(),
			base: $orbitalBase,
			buildingNumber: $buildingIdentifier,
			targetLevel: $targetLevel,
			startedAt: new \DateTimeImmutable(),
			endedAt: new \DateTimeImmutable(),
		);
	}
}
