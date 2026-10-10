<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Battle;

use App\Modules\Ares\Application\Battle\BattleLock;
use App\Modules\Ares\Application\Battle\BattleUpdatePublisherInterface;
use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\GetFactionBattleBonuses;
use App\Modules\Ares\Application\Battle\Handler\ResolveBattleTurnHandler;
use App\Modules\Ares\Application\Battle\JoinBattle;
use App\Modules\Ares\Application\Battle\Message\ResolveBattleTurnMessage;
use App\Modules\Ares\Application\Battle\StartBattle;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\BattleTurn;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Ares\Domain\Repository\BattleTurnRepositoryInterface;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;
use App\Tests\Modules\Ares\Domain\Service\Battle\BattleTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Uid\Uuid;

/**
 * Phase 6: a stored battle resolved turn by turn by its ticker, and fleets joining it while it runs.
 */
class RealTimeBattleTest extends BattleTestCase
{
	private MockClock $clock;
	/** @var list<Envelope> */
	private array $dispatched = [];
	/** @var list<int> turns published to the viewers */
	private array $published = [];
	private BattleRepositoryInterface $battleRepository;
	private BattleTurnRepositoryInterface $battleTurnRepository;
	private BattleLock $battleLock;

	protected function setUp(): void
	{
		$this->clock = new MockClock('2026-10-08 12:00:00');
		$this->battleRepository = new InMemoryBattleRepository();
		$this->battleTurnRepository = new InMemoryBattleTurnRepository();
		$this->battleLock = new BattleLock(new LockFactory(new InMemoryStore()));
	}

	public function testTheStateSurvivesStorage(): void
	{
		$state = $this->createState();
		(new BattleEngine())->resolveTurn($state);
		$battle = new Battle(Uuid::v7(), $state, 10, $this->clock->now());

		// A blob is read back from the database as a stream
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, serialize($state));
		(new \ReflectionProperty(Battle::class, 'state'))->setValue($battle, $stream);
		$restored = $battle->getState();

		static::assertSame(1, $restored->turn);
		static::assertCount(2, $restored->getActiveFleets());
		$squadron = $restored->fleets[0]->squadrons[0];
		static::assertSame($squadron, $restored->getSquadronAt($squadron->cell), 'The cell occupancy is restored');
		static::assertSame($restored->fleets[0], $squadron->getFleet());
	}

	public function testStoredEventsAreRestored(): void
	{
		$state = $this->createState();
		$result = (new BattleEngine())->resolveTurn($state);
		$battleTurn = BattleTurn::fromResult(new Battle(Uuid::v7(), $state, 10, $this->clock->now()), $result, $this->clock->now());

		static::assertEquals($result->toArray(), $battleTurn->toResult()->toArray());
		static::assertSame(BattleEvent::FLEET_JOINED, $battleTurn->toResult()->events[0]->type);
	}

	public function testTheFirstTurnIsResolvedAtOnceThenEveryTurnDuration(): void
	{
		$battle = $this->startBattle();

		static::assertSame([1], $this->dispatchedTurns());
		static::assertSame([], $this->dispatched[0]->all(DelayStamp::class));

		$this->clock->sleep(0.3);
		$this->resolve($battle, 1);

		static::assertSame(1, $battle->turn);
		static::assertCount(1, $this->battleTurnRepository->getBattleTurns($battle));
		static::assertSame([1], $this->published);
		// Turn 2 is due 10 s after the start, not 10 s after the end of turn 1
		static::assertSame([1, 2], $this->dispatchedTurns());
		static::assertSame(9700, $this->lastDelay());
	}

	public function testATurnIsResolvedOnlyOnce(): void
	{
		$battle = $this->startBattle();

		$this->resolve($battle, 1);
		$this->resolve($battle, 1);
		$this->resolve($battle, 3);

		static::assertSame(1, $battle->turn);
		static::assertSame([1], $this->published);
		static::assertSame([1, 2], $this->dispatchedTurns());
	}

	public function testAFleetJoinsAtTheNextTurn(): void
	{
		$battle = $this->startBattle();
		$this->resolve($battle, 1);

		$fleet = $this->joinBattle()($battle->id, ['identifier' => 'reinforcement', 'name' => 'Renfort', 'side' => 1, 'faction' => 0, 'ships' => ['Corvette' => 10]]);
		static::assertSame(2, $fleet->arrivalTurn);

		$this->clock->sleep(10);
		$this->resolve($battle, 2);

		$joined = array_filter(
			$this->battleTurnRepository->getBattleTurns($battle)[1]->events,
			fn (array $event) => BattleEvent::FLEET_JOINED === $event['type'],
		);
		static::assertSame(['reinforcement'], array_column($joined, 'fleet'));
	}

	public function testAFleetCannotJoinTwice(): void
	{
		$battle = $this->startBattle();

		$this->expectException(\DomainException::class);
		$this->joinBattle()($battle->id, ['identifier' => 'defender', 'name' => 'Défenseur', 'side' => 0, 'faction' => 0, 'ships' => ['Corvette' => 10]]);
	}

	public function testTheTickerStopsWhenTheBattleIsOver(): void
	{
		$battle = $this->startBattle();

		for ($turn = 1; !$battle->isOver(); ++$turn) {
			$this->resolve($battle, $turn);
			$this->clock->sleep(10);
		}

		static::assertNotNull($battle->endedAt);
		static::assertSame($battle->turn, max($this->dispatchedTurns()));
		static::assertSame($battle->getState()->winningSide, $battle->winningSide);
		static::assertSame(range(1, $battle->turn), $this->published);
	}

	private function startBattle(): Battle
	{
		return (new StartBattle($this->battleRepository, $this->messageBus(), $this->clock, turnDuration: 10))($this->createState());
	}

	private function resolve(Battle $battle, int $turn): void
	{
		$publisher = new class($this->published) implements BattleUpdatePublisherInterface {
			/** @param list<int> $published */
			public function __construct(private array &$published)
			{
			}

			public function publishTurn(Battle $battle, BattleState $state, TurnResult $turn): void
			{
				$this->published[] = $turn->turn;
			}
		};

		(new ResolveBattleTurnHandler(
			$this->battleRepository,
			$this->battleTurnRepository,
			new BattleEngine(),
			$publisher,
			$this->battleLock,
			$this->messageBus(),
			$this->clock,
		))(new ResolveBattleTurnMessage($battle->id, $turn));
	}

	private function joinBattle(): JoinBattle
	{
		return new JoinBattle($this->battleRepository, $this->createBattle(), $this->battleLock);
	}

	private function createState(): BattleState
	{
		return $this->createBattle()([
			['identifier' => 'defender', 'name' => 'Défenseur', 'side' => 0, 'faction' => 0, 'ships' => ['Corvette' => 10, 'Destroyer' => 1]],
			['identifier' => 'attacker', 'name' => 'Attaquant', 'side' => 1, 'faction' => 0, 'ships' => ['Corvette' => 10, 'LightFighter' => 20]],
		], 42);
	}

	private function createBattle(): CreateBattle
	{
		return new CreateBattle(self::getRegistry(), new GetFactionBattleBonuses([]), battlefieldSize: 5, fleetAreaRadius: 4, formationRadius: 2, durabilityFactor: 6.0);
	}

	private function messageBus(): MessageBusInterface
	{
		return new class($this->dispatched) implements MessageBusInterface {
			/** @param list<Envelope> $dispatched */
			public function __construct(private array &$dispatched)
			{
			}

			public function dispatch(object $message, array $stamps = []): Envelope
			{
				return $this->dispatched[] = Envelope::wrap($message, $stamps);
			}
		};
	}

	/**
	 * @return list<int>
	 */
	private function dispatchedTurns(): array
	{
		return array_values(array_map(
			fn (Envelope $envelope) => $envelope->getMessage()->turn,
			array_filter($this->dispatched, fn (Envelope $envelope) => $envelope->getMessage() instanceof ResolveBattleTurnMessage),
		));
	}

	private function lastDelay(): ?int
	{
		return $this->dispatched[count($this->dispatched) - 1]->last(DelayStamp::class)?->getDelay();
	}
}

/**
 * @internal
 */
class InMemoryBattleRepository implements BattleRepositoryInterface
{
	/** @var array<string, Battle> */
	private array $battles = [];

	public function get(Uuid $id): ?Battle
	{
		return $this->battles[$id->toRfc4122()] ?? null;
	}

	public function getRunningBattleAt(Planet $planet): ?Battle
	{
		return array_find($this->battles, fn (Battle $battle) => $battle->place === $planet && !$battle->isOver());
	}

	public function getPlayerBattles(Player $player, int $limit = 50): array
	{
		return [];
	}

	public function getPlayerBattlesAt(Player $player, array $placeIds): array
	{
		return [];
	}

	public function getFactionBattles(Color $faction, int $limit = 20): array
	{
		return [];
	}

	public function save(object $entity): void
	{
		$this->battles[$entity->id->toRfc4122()] = $entity;
	}

	public function remove(object $entity): void
	{
		unset($this->battles[$entity->id->toRfc4122()]);
	}

	public function detach(object $entity): void
	{
	}

	public function refresh(object $entity): void
	{
	}
}

/**
 * @internal
 */
class InMemoryBattleTurnRepository implements BattleTurnRepositoryInterface
{
	/** @var list<BattleTurn> */
	private array $turns = [];

	public function getBattleTurns(Battle $battle): array
	{
		return array_values(array_filter($this->turns, fn (BattleTurn $turn) => $turn->battle === $battle));
	}

	public function save(object $entity): void
	{
		$this->turns[] = $entity;
	}

	public function remove(object $entity): void
	{
	}

	public function detach(object $entity): void
	{
	}

	public function refresh(object $entity): void
	{
	}
}
