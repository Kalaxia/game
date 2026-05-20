<?php

declare(strict_types=1);

namespace App\Tests\Support\DataFixtures\Foundry\Story;

use App\Modules\Ares\Infrastructure\DataFixtures\Foundry\Factory\CommanderFactory;
use App\Modules\Ares\Infrastructure\DataFixtures\Foundry\Factory\SquadronFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlanetFactory;
use App\Modules\Promethee\Infrastructure\DataFixtures\Factory\TechnologyFactory;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Zenstruck\Foundry\Story;

class CommanderArmyStory extends Story
{
	public function build(): void
	{
		$faction = FactionFactory::createOne([
			'identifier' => ColorResource::KALANKAR,
		]);

		$player = PlayerFactory::createOne([
			'id' => 1,
			'faction' => $faction,
			'name' => 'Mighty player',
		]);

		TechnologyFactory::createOne([
			'player' => $player,
		]);

		$planet = PlanetFactory::createOne([
			'player' => $player,
		]);

		$commander = CommanderFactory::createOne([
			'player' => $player,
			'base' => $planet,
			'level' => 8,
		]);

		SquadronFactory::createOne([
			'commander' => $commander,
			'position' => 6,
			'ship0' => 25,
			'ship1' => 15,
		]);

		SquadronFactory::createMany(5, fn (int $index) => [
			'commander' => $commander,
			'position' => $index - 1,
			'ship1' => 9,
			'ship10' => 1,
		]);
	}
}
