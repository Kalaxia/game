<?php

declare(strict_types=1);

namespace App\Tests\Support\DataFixtures\Foundry\Story;

use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
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
			...self::cell($index - 1),
			'createdAt' => new \DateTimeImmutable('-1 day'),
			'shipCategory' => ShipCategory::Cruiser,
			'ships' => 1,
		]);

		$secondCommander = CommanderFactory::createOne([
			'name' => 'Second officer',
			'player' => $player,
			'base' => $planet,
			'level' => 10,
		]);

		SquadronFactory::createMany(10, fn (int $index) => [
			'commander' => $secondCommander,
			...self::cell($index - 1),
			'createdAt' => new \DateTimeImmutable('-1 day'),
			'shipCategory' => ShipCategory::HeavyCruiser,
			'ships' => 1,
		]);
	}

	/**
	 * @return array{q: int, r: int} a distinct cell of the formation grid for each index
	 */
	private static function cell(int $index): array
	{
		$hex = Hex::origin()->range(2)[$index];

		return ['q' => $hex->q, 'r' => $hex->r];
	}
}
