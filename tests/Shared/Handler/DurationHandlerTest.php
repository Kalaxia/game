<?php

declare(strict_types=1);

namespace App\Tests\Shared\Handler;

use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Enum\PlanetType;
use App\Modules\Galaxy\Domain\Enum\SystemType;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use App\Shared\Domain\Model\DurationInterface;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class DurationHandlerTest extends TestCase
{
	private DurationHandler $durationHandler;

	public function setUp(): void
	{
		$this->durationHandler = new DurationHandler();
	}

	/**
	 * @dataProvider provideGetHoursDiffData
	 */
	public function testGetHoursDiff(\DateTimeImmutable $from, \DateTimeImmutable $to, int $expectedHours): void
	{
		static::assertSame(
			$expectedHours,
			$this->durationHandler->getHoursDiff($from, $to),
		);
	}

	/**
	 * @dataProvider provideGetRemainingTimeData
	 */
	public function testGetRemainingTime(DurationInterface $duration, int $expectedTime): void
	{
		static::assertSame(
			round($expectedTime / 100),
			round($this->durationHandler->getDurationRemainingTime($duration) / 100),
		);
	}

	/**
	 * @dataProvider provideGetDurationEndData
	 */
	public function testGetDurationEnd(\DateTimeImmutable $startedAt, int $seconds, \DateTimeInterface $expected): void
	{
		static::assertSame(
			$expected->getTimestamp(),
			$this->durationHandler->getDurationEnd($startedAt, $seconds)->getTimestamp(),
		);
	}

	/**
	 * @return Generator<array<{0: \DateTimeImmutable, 1: int, 2: \DateTimeImmutable}>>
	 */
	public static function provideGetDurationEndData(): \Generator
	{
		yield [
			new \DateTimeImmutable(),
			3600,
			new \DateTimeImmutable('+3600 seconds'),
		];

		yield [
			new \DateTimeImmutable(),
			10,
			new \DateTimeImmutable('+10 seconds'),
		];

		yield [
			new \DateTimeImmutable(),
			600,
			new \DateTimeImmutable('+10 minutes'),
		];
	}

	/**
	 * @return Generator<array<{0: DurationInterface, 1: int}>>
	 */
	public static function provideGetRemainingTimeData(): \Generator
	{
		yield [
			static::generateBuildingQueue(new \DateTimeImmutable('+1 hour')),
			3600,
		];
		yield [
			static::generateBuildingQueue(new \DateTimeImmutable('+1 day')),
			3600 * 24,
		];
		yield [
			static::generateBuildingQueue(new \DateTimeImmutable('-1 hour')),
			0,
		];
	}

	/**
	 * @return Generator<array<{0: \DateTimeImmutable, 1: \DateTimeImmutable, 2: int}>>
	 */
	public static function provideGetHoursDiffData(): \Generator
	{
		yield [
			new \DateTimeImmutable('+2 hours'),
			new \DateTimeImmutable('+4 hours'),
			2,
		];

		yield [
			new \DateTimeImmutable('2022-01-05 10:00:00'),
			new \DateTimeImmutable('2022-01-07 14:00:00'),
			52,
		];

		yield [
			new \DateTimeImmutable('2022-01-07 14:00:00'),
			new \DateTimeImmutable('2022-01-05 10:00:00'),
			52,
		];
	}

	private static function generateBuildingQueue(\DateTimeImmutable $endedAt): BuildingQueue
	{
		return new BuildingQueue(
			id: Uuid::v4(),
			base: new Planet(
				id: Uuid::v4(),
				system: new System(
					id: Uuid::v4(),
					sector: new Sector(
						id: Uuid::v4(),
						identifier: 1,
						faction: null,
						xPosition: 10,
						yPosition: 10,
						xBarycentric: 0,
						yBarycentric: 0,
						tax: 5,
						name: null,
						points: 5,
						population: 0,
						prime: true,
					),
					faction: null,
					xPosition: 10,
					yPosition: 20,
					typeOfSystem: SystemType::Nebula,
				),
				position: 1,
				planetType: PlanetType::Ice,
				player: new Player(),
				name: 'My wonderful base',
				population: 100,
				coefResources: 60,
				coefHistory: 20,
				danger: 40,
				maxDanger: 60,
				levelGenerator: 3,
				levelRefinery: 2,
				levelCommercialPlateforme: 0,
				levelStorage: 5,
				updatedAt: new \DateTimeImmutable(),
			),
			buildingNumber: PlanetResource::SPATIOPORT,
			targetLevel: 10,
			startedAt: new \DateTimeImmutable(),
			endedAt: $endedAt,
		);
	}
}
