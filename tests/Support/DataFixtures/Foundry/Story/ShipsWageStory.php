<?php

declare(strict_types=1);

namespace App\Tests\Support\DataFixtures\Foundry\Story;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Infrastructure\DataFixtures\Foundry\Factory\CommanderFactory;
use App\Modules\Ares\Infrastructure\DataFixtures\Foundry\Factory\SquadronFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlanetFactory;
use App\Modules\Promethee\Infrastructure\DataFixtures\Factory\TechnologyFactory;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Zenstruck\Foundry\Story;

class ShipsWageStory extends Story
{
	public function build(): void
	{
		$faction = FactionFactory::createOne([
			'identifier' => ColorResource::KALANKAR,
		]);

		PlayerFactory::createOne([
			'id' => 1,
			'name' => 'Rebel',
		]);

		$player = PlayerFactory::createOne([
			'id' => 2,
			'faction' => $faction,
			'name' => 'Ruined player',
		]);

		TechnologyFactory::createOne([
			'player' => $player,
		]);

		$planet = PlanetFactory::createOne([
			'player' => $player,
			'shipStorage' => [
				ShipCategory::Fighter->value => 100,
			],
		]);

		$commander = CommanderFactory::createOne([
			'name' => 'First officer',
			'player' => $player,
			'level' => 5,
			'base' => $planet,
		]);

		SquadronFactory::createMany(5, fn (int $index) => [
			'commander' => $commander,
			'position' => $index - 1,
			'createdAt' => new \DateTimeImmutable('-1 day'),
			'ship10' => 1,
		]);

		$secondCommander = CommanderFactory::createOne([
			'name' => 'Second officer',
			'player' => $player,
			'base' => $planet,
			'level' => 10,
		]);

		SquadronFactory::createMany(10, fn (int $index) => [
			'commander' => $secondCommander,
			'position' => $index - 1,
			'createdAt' => new \DateTimeImmutable('-1 day'),
			'ship11' => 1,
		]);
	}
}
