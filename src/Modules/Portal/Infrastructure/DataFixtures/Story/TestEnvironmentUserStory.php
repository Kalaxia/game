<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\DataFixtures\Story;

use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlanetFactory;
use App\Modules\Portal\Infrastructure\DataFixtures\Factory\UserFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'test_environment_user', groups: ['test_environment'])]
class TestEnvironmentUserStory extends Story
{
	public function build(): void
	{
		$this->addState('tester_user', UserFactory::createOne([
			'email' => 'tester@example.org',
			'username' => 'tester',
		]));
	}
}
