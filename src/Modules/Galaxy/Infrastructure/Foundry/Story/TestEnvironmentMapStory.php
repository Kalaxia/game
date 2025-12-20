<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\Foundry\Story;

use App\Modules\Demeter\Infrastructure\DataFixtures\Story\TestEnvironmentFactionsStory;
use App\Modules\Economy\Infrastructure\Foundry\Story\TestEnvironmentCompaniesStory;
use App\Modules\Galaxy\Domain\Service\CreateMultipleSystemCoordinates;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlanetFactory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SectorFactory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SystemFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'test_environment_map', groups: ['test_environment'])]
class TestEnvironmentMapStory extends Story
{
	private const SYSTEMS_PER_SECTOR_TARGET_COUNT = 20;

	public function __construct(
		private readonly CreateMultipleSystemCoordinates $createMultipleSystemCoordinates,
		private readonly GalaxyConfiguration $configuration,
	) {
	}

	public function build(): void
	{
		TestEnvironmentFactionsStory::load();
		TestEnvironmentCompaniesStory::load();

		foreach ($this->configuration->sectors as $sector) {
			$faction = null !== $sector['beginColor']
				? TestEnvironmentFactionsStory::get(sprintf('faction_%d', $sector['beginColor']))
				: null;

			$sector = SectorFactory::createOne([
				'identifier' => $sector['id'],
				'faction' => $faction,
				'xBarycentric' => $sector['barycentre'][0],
				'yBarycentric' => $sector['barycentre'][1],
				'xPosition' => $sector['display'][0],
				'yPosition' => $sector['display'][1],
				'points' => $sector['points'],
				'name' => $sector['name'],
			]);
			$this->addState(sprintf('sector_%d', $sector->identifier), $sector);

			if ($faction !== null) {
				$this->addState(sprintf('faction_%d_sector', $faction->identifier), $sector);
			}

			$points = ($this->createMultipleSystemCoordinates)(
				sector: $sector,
				targetCount: self::SYSTEMS_PER_SECTOR_TARGET_COUNT,
				maxAttempts: self::SYSTEMS_PER_SECTOR_TARGET_COUNT * 40,
			);

			foreach ($points as [$x, $y]) {
				$system = SystemFactory::createOne([
					'sector' => $sector,
					'xPosition' => $x,
					'yPosition' => $y,
				]);

				foreach (range(1, random_int(2, 6)) as $position) {
					$planet = PlanetFactory::createOne([
						'position' => $position,
						'system' => $system,
					]);
				}
			}
		}
	}
}
