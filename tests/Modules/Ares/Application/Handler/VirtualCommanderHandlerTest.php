<?php

namespace App\Tests\Modules\Ares\Application\Handler;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Application\Handler\VirtualCommanderHandler;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class VirtualCommanderHandlerTest extends KernelTestCase
{
	/**
	 * @dataProvider provideData
	 */
	public function testCreateVirtualCommander(Place $place, int $expectedSquadronsCount, int $expectedPev): void
	{
		static::bootKernel();

		$handler = static::getContainer()->get(VirtualCommanderHandler::class);
		$armyHandler = static::getContainer()->get(CommanderArmyHandler::class);

		$commander = $handler->createVirtualCommander($place);

		static::assertSame('rebelle', $commander->name);
		static::assertTrue($commander->isAffected());
		static::assertSame($expectedSquadronsCount, $commander->level);
		static::assertCount($expectedSquadronsCount, $commander->squadronsIds);
		static::assertCount($expectedSquadronsCount, $commander->army);
		static::assertSame($expectedPev, $armyHandler->getPev($commander));
	}

	public static function provideData(): \Generator
	{
		yield [
			static::getPlaceMock(
				danger: 0,
				maxDanger: 10,
				population: 147,
				position: 1,
				historyCoeff: 20,
				resourcesCoeff: 60,
			),
			3,
			18,
		];
		yield [
			static::getPlaceMock(
				danger: 3,
				maxDanger: 10,
				population: 147,
				position: 1,
				historyCoeff: 20,
				resourcesCoeff: 60,
			),
			3,
			36,
		];
		yield [
			static::getPlaceMock(
				danger: 0,
				maxDanger: 7,
				population: 100,
				position: 1,
				historyCoeff: 10,
				resourcesCoeff: 40,
			),
			3,
			18,
		];
		yield [
			static::getPlaceMock(
				danger: 3,
				maxDanger: 7,
				population: 100,
				position: 3,
				historyCoeff: 70,
				resourcesCoeff: 10,
			),
			3,
			36,
		];
	}

	private static function getPlaceMock(
		int $danger,
		int $maxDanger,
		int $population,
		int $position,
		int $historyCoeff,
		int $resourcesCoeff,
	): Place {
		return new Place(
			id: Uuid::v4(),
			player: null,
			base: null,
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
					prime: 1,
				),
				faction: null,
				xPosition: 10,
				yPosition: 10,
				typeOfSystem: 0,
			),
			typeOfPlace: Place::TERRESTRIAL,
			position: $position,
			population: $population,
			coefResources: $resourcesCoeff,
			coefHistory: $historyCoeff,
			resources: 0,
			danger: $danger,
			maxDanger: $maxDanger,
			updatedAt: new \DateTimeImmutable(),
		);
	}
}
