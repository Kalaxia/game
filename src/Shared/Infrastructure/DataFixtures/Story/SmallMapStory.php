<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DataFixtures\Story;

use App\Modules\Athena\Infrastructure\DataFixtures\Factory\OrbitalBaseFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\PlaceFactory;
use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\SectorFactory;
use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\SystemFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

use function Zenstruck\Foundry\Persistence\flush_after;

#[AsFixture('small-map')]
class SmallMapStory extends Story
{
	public function build(): void
	{
		flush_after(function (): void {
			FactionFactory::createMany(3);

			SectorFactory::createMany(3);

			SystemFactory::createMany(50);

			PlaceFactory::createMany(250);

			OrbitalBaseFactory::createMany(10);
		});
	}
}
