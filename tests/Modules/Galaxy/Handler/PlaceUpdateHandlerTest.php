<?php

namespace App\Tests\Modules\Galaxy\Handler;

use App\Modules\Galaxy\Handler\PlaceUpdateHandler;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlaceFactory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SectorFactory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SystemFactory;
use App\Modules\Galaxy\Message\PlanetUpdateMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Generator;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Zenstruck\Foundry\Test\Factories;

class PlaceUpdateHandlerTest extends KernelTestCase
{
	use Factories;

	/**
	 * @param array<string, mixed> $placeData
	 */
	#[DataProvider('provideData')]
	public function test(string $currentDate, string $timeMode, array $placeData, int $expectedResources, int $expectedDanger): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode;
		Clock::set(new MockClock($currentDate));
		static::bootKernel();
		/** @var PlaceUpdateHandler $handler */
		$updatePlace = static::getContainer()->get(PlaceUpdateHandler::class);

		$sector = SectorFactory::createOne();
		$system = SystemFactory::createOne(['sector' => $sector->object()]);
		$place = PlaceFactory::createOne([
			'system' => $system->object(),
			'updatedAt' => new \DateTimeImmutable($placeData['updated_at']),
			'resources' => $placeData['resources'],
			'population' => $placeData['population'],
			'danger' => $placeData['danger'],
			'maxDanger' => $placeData['max_danger'],
			'coefResources' => $placeData['resources_coeff'],
		])->object();

		$updatePlace(new PlanetUpdateMessage($place->id));

		static::assertSame($expectedResources, $place->resources);
		static::assertSame($expectedDanger, $place->danger);
	}

	/**
	 * @return Generator<array{
	 *     0: string,
	 * 	   1: string,
	 *     2: array<string, mixed>,
	 *     3: int,
	 *     4: int
	 * }>
	 */
	public static function provideData(): Generator
	{
		yield 'Update once' => [
			'2024-05-01 10:10:00',
			'fast',
			[
				'resources' => 1890,
				'resources_coeff' => 29,
				'population' => 105.0,
				'danger' => 5,
				'max_danger' => 7,
				'updated_at' => '2024-05-01 10:00:00',
			],
			2100,
			7,
		];

		yield 'Update once and reach place max resources' => [
			'2024-05-01 10:10:00',
			'fast',
			[
				'resources' => 14300,
				'resources_coeff' => 60,
				'population' => 105.0,
				'danger' => 4,
				'max_danger' => 7,
				'updated_at' => '2024-05-01 10:00:00',
			],
			14400,
			6,
		];

		yield 'Multiple updates and reach max danger' => [
			'2024-05-01 10:32:00',
			'fast',
			[
				'resources' => 1890,
				'resources_coeff' => 29,
				'population' => 105.0,
				'danger' => 5,
				'max_danger' => 7,
				'updated_at' => '2024-05-01 10:00:00',
			],
			2520,
			7,
		];
	}
}
