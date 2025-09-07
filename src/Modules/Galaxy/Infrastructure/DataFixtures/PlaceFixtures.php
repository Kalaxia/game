<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures;

use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlaceFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlaceFixtures extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		PlaceFactory::createMany(250);
	}

	public function getDependencies(): array
	{
		return [
			SystemFixtures::class,
		];
	}
}
