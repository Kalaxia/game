<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Battle;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\GenerateRebelFleet;
use App\Modules\Demeter\Resource\ColorResource;
use App\Tests\Modules\Ares\Support\BuildPlanet;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Rebel fleets (D16) follow the legacy formula of the virtual commanders.
 */
class GenerateRebelFleetTest extends KernelTestCase
{
	/**
	 * @param array{danger: int, maxDanger: int, population: int, position: int, historyCoeff: int, resourcesCoeff: int} $planet
	 */
	#[DataProvider('provideLegacyCases')]
	public function testLegacyStrength(array $planet, int $expectedLevel, int $expectedPev): void
	{
		$generator = static::getContainer()->get(GenerateRebelFleet::class);
		$planet = BuildPlanet::build(...$planet);

		static::assertSame($expectedLevel, $generator->getLevel($planet));
		static::assertSame($expectedPev, $generator->getPev($planet));
	}

	public static function provideLegacyCases(): \Generator
	{
		yield [['danger' => 0, 'maxDanger' => 10, 'population' => 147, 'position' => 1, 'historyCoeff' => 20, 'resourcesCoeff' => 60], 3, 18];
		yield [['danger' => 3, 'maxDanger' => 10, 'population' => 147, 'position' => 1, 'historyCoeff' => 20, 'resourcesCoeff' => 60], 3, 36];
		yield [['danger' => 0, 'maxDanger' => 7, 'population' => 100, 'position' => 1, 'historyCoeff' => 10, 'resourcesCoeff' => 40], 3, 18];
		yield [['danger' => 3, 'maxDanger' => 7, 'population' => 100, 'position' => 3, 'historyCoeff' => 70, 'resourcesCoeff' => 10], 3, 36];
	}

	public function testTheStrongestRebelsFitInAFormation(): void
	{
		$generator = static::getContainer()->get(GenerateRebelFleet::class);
		$planet = BuildPlanet::build(danger: 100, maxDanger: 100, population: 250, historyCoeff: 97, resourcesCoeff: 83);

		$fleet = $generator->describe($planet, 0);
		$state = static::getContainer()->get(CreateBattle::class)([$fleet], 1);

		static::assertSame(ColorResource::NO_FACTION, $fleet['faction']);
		static::assertSame(20, $fleet['officer_level']);
		static::assertNotEmpty($state->fleets[0]->formation);
	}
}
