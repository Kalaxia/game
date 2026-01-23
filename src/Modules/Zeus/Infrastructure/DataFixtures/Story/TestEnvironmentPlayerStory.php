<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\DataFixtures\Story;

use App\Modules\Demeter\Infrastructure\DataFixtures\Story\TestEnvironmentFactionsStory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlanetFactory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SectorFactory;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\SystemFactory;
use App\Modules\Galaxy\Infrastructure\Repository\Doctrine\PlanetRepository;
use App\Modules\Portal\Infrastructure\DataFixtures\Story\TestEnvironmentUserStory;
use App\Modules\Promethee\Infrastructure\DataFixtures\Factory\TechnologyFactory;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'test_environment_player', groups: ['test_environment'])]
class TestEnvironmentPlayerStory extends Story
{
	public function __construct(private readonly PlanetRepository $planetRepository)
	{
	}

	public function build(): void
	{
		$user = TestEnvironmentUserStory::get('tester_user');
		$faction = TestEnvironmentFactionsStory::get('faction_1');

		$player = PlayerFactory::createOne([
			'user' => $user,
			'faction' => $faction,
		]);

		TechnologyFactory::createOne([
			'player' => $player,
		]);

		$sector = SectorFactory::find([
			'identifier' => 1,
		]);

		$system = SystemFactory::random([
			'sector' => $sector,
		]);

		$planet = PlanetFactory::random([
			'system' => $system,
		]);

		$planet->player = $player;

		$this->planetRepository->save($planet);
	}
}
