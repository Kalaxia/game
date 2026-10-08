<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Tests\Modules\Ares\Domain\Service\Battle\BattleTestCase;

class BattleEventTest extends BattleTestCase
{
	public function testFleetMovedCarriesTheTranslationAndMovedSquadrons(): void
	{
		$fleet = new BattleFleet(identifier: 'a', name: 'a', faction: 1, side: 0, officerLevel: 1, formation: []);
		$fleet->cluster = new Hex(2, 1);
		$squadron = new BattleSquadron('a-0', self::shipType('Corvette'), 5, new Hex(20, 9), $fleet);

		$event = BattleEvent::fleetMoved($fleet, new Hex(1, 1), new Hex(9, -4), [$squadron])->toArray();

		static::assertSame([
			'type' => 'fleet_moved',
			'fleet' => 'a',
			'from' => ['q' => 1, 'r' => 1],
			'to' => ['q' => 2, 'r' => 1],
			'translation' => ['q' => 9, 'r' => -4],
			'squadrons' => ['a-0'],
		], $event);
	}
}
