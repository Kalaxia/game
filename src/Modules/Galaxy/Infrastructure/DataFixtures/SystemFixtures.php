<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures;

use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SystemFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SystemFixtures extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		SystemFactory::createMany(40);
	}

	public function getDependencies(): array
	{
		return [
			SectorFixtures::class,
		];
	}
}
