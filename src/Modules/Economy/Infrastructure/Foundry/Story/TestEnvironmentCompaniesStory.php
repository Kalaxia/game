<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Foundry\Story;

use App\Modules\Demeter\Infrastructure\DataFixtures\Story\TestEnvironmentFactionsStory;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Infrastructure\Foundry\Factory\CompanyFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture('test_environment_companies', groups: ['test_environment'])]
class TestEnvironmentCompaniesStory extends Story
{
	public function build(): void
	{
		$factions = TestEnvironmentFactionsStory::getPool('factions');

		foreach ($factions as $faction) {
			foreach (Activity::cases() as $activity) {
				CompanyFactory::createMany(3, [
					'faction' => $faction,
					'activity' => $activity,
				]);
			}
		}
	}
}
