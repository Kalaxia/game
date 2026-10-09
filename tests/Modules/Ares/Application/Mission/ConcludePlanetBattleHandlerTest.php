<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Mission;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\DescribeCommanderFleet;
use App\Modules\Ares\Application\Battle\GenerateRebelFleet;
use App\Modules\Ares\Application\Battle\GetFactionBattleBonuses;
use App\Modules\Ares\Application\Battle\Message\BattleEndedMessage;
use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Application\Handler\Movement\MoveFleet;
use App\Modules\Ares\Application\Mission\AccomplishMission;
use App\Modules\Ares\Application\Mission\BattleNotifier;
use App\Modules\Ares\Application\Mission\ConcludePlanetBattleHandler;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Ares\Domain\Service\Battle\ComputeBattleExperience;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Tests\Modules\Ares\Domain\Service\Battle\BattleTestCase;
use App\Tests\Modules\Ares\Support\BuildCommander;
use App\Tests\Modules\Ares\Support\BuildPlanet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Uid\Uuid;

/**
 * Outcome of a battle in the game (D15, D16, R7).
 */
class ConcludePlanetBattleHandlerTest extends BattleTestCase
{
	public function testTheWinnerCarriesOutItsMissionAndTheRebelsLoseTheirDanger(): void
	{
		$planet = BuildPlanet::build(danger: 6, maxDanger: 10);
		$attacker = $this->attacker($planet, [[ShipCategory::Destroyer, 1, 0, 0], [ShipCategory::Corvette, 14, 1, 0], [ShipCategory::LightFighter, 50, 0, 1]]);
		$battle = $this->fight($planet, [
			['identifier' => GenerateRebelFleet::IDENTIFIER_PREFIX.'test', 'name' => 'Rebelles', 'side' => 0, 'faction' => 0, 'ships' => ['LightFighter' => 40, 'Corvette' => 6]],
			$this->describe($attacker, 1),
		]);

		$accomplishMission = $this->createMock(AccomplishMission::class);
		$accomplishMission->expects(static::once())->method('__invoke')->with($attacker, $battle);

		$this->conclude($battle, [$attacker], $accomplishMission);

		static::assertSame(1, $battle->getState()->winningSide);
		static::assertNull($attacker->battle);
		static::assertSame(1, $attacker->palmares);
		static::assertGreaterThan(0, $attacker->experience);
		static::assertSame(0, $planet->danger, 'The rebels were destroyed');
		static::assertLessThanOrEqual(65, array_sum(array_map(fn (Squadron $squadron) => $squadron->ships, $attacker->squadrons->toArray())));
	}

	public function testADestroyedFleetLosesItsOfficer(): void
	{
		$defenderPlayer = BuildCommander::player(2, 3);
		$planet = BuildPlanet::build(player: $defenderPlayer);
		$defender = BuildCommander::build($defenderPlayer, $planet, [[ShipCategory::Destroyer, 1, 0, 0], [ShipCategory::Destroyer, 1, 1, 0], [ShipCategory::Corvette, 14, 0, 1]], name: 'defender');
		$attacker = $this->attacker($planet, [[ShipCategory::LightFighter, 5, 0, 0]]);
		$battle = $this->fight($planet, [$this->describe($defender, 0), $this->describe($attacker, 1)]);

		$accomplishMission = $this->createMock(AccomplishMission::class);
		$accomplishMission->expects(static::never())->method('__invoke');

		$this->conclude($battle, [$defender, $attacker], $accomplishMission);

		static::assertTrue($attacker->isDead());
		static::assertCount(0, $attacker->squadrons);
		static::assertSame(1, $attacker->player->defeat);
		static::assertTrue($defender->isAffected(), 'A defender stays at its base');
		static::assertSame(1, $defenderPlayer->victory);
	}

	private function attacker(Planet $target, array $squadrons): Commander
	{
		$player = BuildCommander::player(1, 2);
		$base = BuildPlanet::build(player: $player);
		$commander = BuildCommander::build($player, $base, $squadrons, name: 'attacker');
		$commander->travelType = CommanderMission::Loot;
		$commander->startPlace = $base;
		$commander->destinationPlace = $target;

		return $commander;
	}

	/**
	 * Same description as DescribeCommanderFleet, without the player bonuses.
	 *
	 * @return array<string, mixed>
	 */
	private function describe(Commander $commander, int $side): array
	{
		return [
			'identifier' => DescribeCommanderFleet::getIdentifier($commander),
			'name' => $commander->name,
			'side' => $side,
			'faction' => $commander->player->faction->identifier,
			'officer_level' => $commander->level,
			'formation' => array_map(fn (Squadron $squadron) => [
				'type' => $squadron->shipCategory->name,
				'ships' => $squadron->ships,
				'q' => $squadron->q,
				'r' => $squadron->r,
			], DescribeCommanderFleet::getSquadrons($commander)),
		];
	}

	/**
	 * @param list<array<string, mixed>> $fleets
	 */
	private function fight(Planet $planet, array $fleets): Battle
	{
		$state = (new CreateBattle(self::getRegistry(), new GetFactionBattleBonuses([]), battlefieldSize: 5, fleetAreaRadius: 4, formationRadius: 2, durabilityFactor: 6.0))($fleets, 7);
		(new BattleEngine())->resolveBattle($state);

		$battle = new Battle(Uuid::v7(), $state, 10, new \DateTimeImmutable('-10 minutes'), place: $planet);
		$battle->end(new \DateTimeImmutable());

		return $battle;
	}

	/**
	 * @param list<Commander> $commanders
	 */
	private function conclude(Battle $battle, array $commanders, AccomplishMission $accomplishMission): void
	{
		foreach ($commanders as $commander) {
			$commander->statement = Commander::IN_BATTLE;
			$commander->battle = $battle;
		}

		$battleRepository = $this->createStub(BattleRepositoryInterface::class);
		$battleRepository->method('get')->willReturn($battle);
		$commanderRepository = $this->createStub(CommanderRepositoryInterface::class);
		$commanderRepository->method('getBattleCommanders')->willReturn($commanders);

		(new ConcludePlanetBattleHandler(
			$battleRepository,
			$commanderRepository,
			$this->createStub(PlanetRepositoryInterface::class),
			new ComputeBattleExperience(0.006),
			new CommanderExperienceHandler($commanderRepository, $this->createStub(PlayerManager::class), 100),
			$accomplishMission,
			$this->createStub(MoveFleet::class),
			$this->createStub(BattleNotifier::class),
			$this->createStub(EntityManagerInterface::class),
			new LockFactory(new InMemoryStore()),
			new MockClock(),
		))(new BattleEndedMessage($battle->id));
	}
}
