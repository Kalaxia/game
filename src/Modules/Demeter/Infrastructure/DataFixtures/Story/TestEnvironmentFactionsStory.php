<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Story;

use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'test_environment_factions', groups: ['test_environment'])]
class TestEnvironmentFactionsStory extends Story
{
	public function __construct(
		#[Autowire('%game.available_factions%')]
		private array $availableFactionIdentifiers,
	) {
	}

	public function build(): void
	{
		foreach ($this->availableFactionIdentifiers as $factionIdentifier) {
			$faction = FactionFactory::createOne([
				'identifier' => $factionIdentifier,
			]);
			$this->addState(sprintf('faction_%s', $factionIdentifier), $faction);
			$this->addToPool('factions', $faction);
		}
	}
}
