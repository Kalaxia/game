<?php

declare(strict_types=1);

namespace App\Tests\Modules\Galaxy\Application\Handler;

use App\Modules\Galaxy\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlaceFactory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SectorFactory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SystemFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GetDistanceBetweenPlacesTest extends KernelTestCase
{
	#[DataProvider('provideData')]
	public function test(int $xA, int $yA, int $xB, int $yB, float $expectedDistance): void
	{
		static::bootKernel();
		$getDistanceBetweenPlaces = new GetDistanceBetweenPlaces();

		$from = PlaceFactory::createOne([
			'system' => SystemFactory::createOne([
				'sector' => SectorFactory::createOne([])->object(),
				'xPosition' => $xA,
				'yPosition' => $yA,
			])->object(),
		])->object();

		$to = PlaceFactory::createOne([
			'system' => SystemFactory::createOne([
				'sector' => SectorFactory::createOne([])->object(),
				'xPosition' => $xB,
				'yPosition' => $yB,
			])->object(),
		])->object();

		static::assertSame($expectedDistance, $getDistanceBetweenPlaces($from, $to));
	}

	public static function provideData(): \Generator
	{
		yield [
			20,
			25,
			40,
			60,
			40.0,
		];

		yield [
			13,
			17,
			13,
			14,
			3.0,
		];

		yield [
			26,
			17,
			13,
			16,
			13.0,
		];
	}
}
