<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Mission;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\DescribeCommanderFleet;
use App\Modules\Ares\Application\Battle\GenerateRebelFleet;
use App\Modules\Ares\Application\Battle\GetFactionBattleBonuses;
use App\Modules\Ares\Application\Battle\JoinBattle;
use App\Modules\Ares\Application\Battle\StartBattle;
use App\Modules\Ares\Application\Mission\BattleNotifier;
use App\Modules\Ares\Application\Mission\PlanetBattle;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\Battle\FactionHostilityInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Tests\Modules\Ares\Domain\Service\Battle\BattleTestCase;
use App\Tests\Modules\Ares\Support\BuildCommander;
use App\Tests\Modules\Ares\Support\BuildPlanet;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

/**
 * Side taken by a fleet arriving during a battle (Q2, Q3) when its mission designates the enemy (D14).
 * Factions 2 and 3 fight over a planet of faction 3; factions 4 and 5 are neutral to everyone,
 * except 5, at war with faction 2.
 */
class PlanetBattleTest extends BattleTestCase
{
	#[DataProvider('provideArrivals')]
	public function testSideOfAnArrivingFleet(int $faction, CommanderMission $mission, ?int $expectedSide): void
	{
		$owner = BuildCommander::player(3, 3);
		// Players under level 2 cannot be looted
		$owner->level = 5;
		$planet = BuildPlanet::build(player: $owner);
		$battle = $this->planetBattleAt($planet);
		$commander = BuildCommander::build(BuildCommander::player(10, $faction), BuildPlanet::build(), [[ShipCategory::Corvette, 14, 0, 0]]);
		$commander->travelType = $mission;
		$commander->destinationPlace = $planet;

		$joinedSide = null;
		$joinBattle = $this->createMock(JoinBattle::class);
		$joinBattle->expects(null === $expectedSide ? static::never() : static::once())
			->method('__invoke')
			->willReturnCallback(function (Uuid $battleId, array $fleet) use (&$joinedSide) {
				$joinedSide = $fleet['side'];

				return (new CreateBattle(self::getRegistry(), new GetFactionBattleBonuses([]), 5, 4, 2))->createFleet($fleet);
			});

		$joined = $this->planetBattle($joinBattle)->join($battle, $commander);

		static::assertSame(null !== $expectedSide, $joined);
		static::assertSame($expectedSide, $joinedSide);
		static::assertSame(null !== $expectedSide ? Commander::IN_BATTLE : Commander::AFFECTED, $commander->statement);
	}

	public static function provideArrivals(): \Generator
	{
		yield 'a reinforcement of the attackers' => [2, CommanderMission::Move, 1];
		yield 'a defender coming back home' => [3, CommanderMission::Back, 0];
		yield 'a neutral fleet passing by' => [4, CommanderMission::Move, null];
		yield 'a neutral fleet looting the planet joins the attackers' => [4, CommanderMission::Loot, 1];
		yield 'an enemy of the attackers joins the defenders' => [5, CommanderMission::Move, 0];
		yield 'an enemy of the attackers looting the planet fights everyone' => [5, CommanderMission::Loot, 2];
	}

	private function planetBattleAt(Planet $planet): Battle
	{
		$state = (new CreateBattle(self::getRegistry(), new GetFactionBattleBonuses([]), 5, 4, 2))([
			['identifier' => 'defender', 'name' => 'defender', 'side' => 0, 'faction' => 3, 'ships' => ['Corvette' => 14]],
			['identifier' => 'attacker', 'name' => 'attacker', 'side' => 1, 'faction' => 2, 'ships' => ['Corvette' => 14]],
		], 1);

		return new Battle(Uuid::v7(), $state, 10, new \DateTimeImmutable(), place: $planet);
	}

	private function planetBattle(JoinBattle $joinBattle): PlanetBattle
	{
		$hostility = new class implements FactionHostilityInterface {
			public function areEnemies(int $faction, int $otherFaction): bool
			{
				return [2, 3] === [min($faction, $otherFaction), max($faction, $otherFaction)]
					|| [2, 5] === [min($faction, $otherFaction), max($faction, $otherFaction)];
			}
		};
		$describe = $this->createStub(DescribeCommanderFleet::class);
		$describe->method('__invoke')->willReturnCallback(fn (Commander $commander, int $side) => [
			'identifier' => $commander->id->toRfc4122(),
			'name' => $commander->name,
			'side' => $side,
			'faction' => $commander->player->faction->identifier,
			'ships' => ['Corvette' => 14],
		]);

		return new PlanetBattle(
			$this->createStub(CreateBattle::class),
			$this->createStub(StartBattle::class),
			$joinBattle,
			$describe,
			$this->createStub(GenerateRebelFleet::class),
			$hostility,
			$this->createStub(CommanderRepositoryInterface::class),
			$this->createStub(BattleNotifier::class),
		);
	}
}
