<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\DataFixtures;

use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\PlanetFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlanetFixtures extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		PlanetFactory::createMany(10);
	}

	public function getDependencies(): array
	{
		return [
			PlaceFixtures::class,
		];
	}
}
