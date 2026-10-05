<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Battle;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\GetFactionBattleBonuses;
use App\Modules\Ares\Domain\Model\Battle\FormationSlot;
use App\Tests\Modules\Ares\Domain\Service\Battle\BattleTestCase;

class CreateBattleTest extends BattleTestCase
{
	public function testPlayerFormationIsKeptAsComposed(): void
	{
		$state = $this->createBattle()([
			['name' => 'player', 'side' => 0, 'faction' => 0, 'formation' => [
				['type' => 'Cruiser', 'ships' => 1, 'q' => -1, 'r' => 0],
				['type' => 'Corvette', 'ships' => 10, 'q' => 2, 'r' => -1],
			]],
			['name' => 'npc', 'side' => 1, 'faction' => 0, 'ships' => ['LightFighter' => 50]],
		], 1);

		$formation = array_map(
			fn (FormationSlot $slot) => [$slot->type->identifier, $slot->ships, $slot->offset->q, $slot->offset->r],
			$state->fleets[0]->formation,
		);

		static::assertSame([['Cruiser', 1, -1, 0], ['Corvette', 10, 2, -1]], $formation);
		static::assertCount(1, $state->fleets[1]->formation, 'A fleet given by its ships only is arranged automatically');
	}

	public function testInvalidPlayerFormationIsRejected(): void
	{
		$this->expectException(\DomainException::class);
		$this->expectExceptionMessage('Invalid formation for fleet player');

		$this->createBattle()([
			['name' => 'player', 'side' => 0, 'faction' => 0, 'formation' => [
				['type' => 'Corvette', 'ships' => 10, 'q' => 0, 'r' => 0],
				['type' => 'Frigate', 'ships' => 2, 'q' => 0, 'r' => 0],
			]],
		], 1);
	}

	private function createBattle(): CreateBattle
	{
		return new CreateBattle(self::getRegistry(), new GetFactionBattleBonuses([]), battlefieldSize: 5, fleetAreaRadius: 4, formationRadius: 2);
	}
}
