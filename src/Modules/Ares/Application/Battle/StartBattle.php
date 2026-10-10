<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Application\Battle\Message\ResolveBattleTurnMessage;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Planet;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Stores a new battle and starts its ticker: the first turn is resolved at once, the next ones
 * every BATTLE_TURN_DURATION seconds (Q6).
 */
readonly class StartBattle
{
	public function __construct(
		private BattleRepositoryInterface $battleRepository,
		private MessageBusInterface $messageBus,
		private ClockInterface $clock,
		#[Autowire('%ares.battle.turn_duration%')]
		private int $turnDuration,
	) {
	}

	public function __invoke(BattleState $state, ?Planet $place = null): Battle
	{
		$battle = new Battle(
			id: Uuid::v7(),
			state: $state,
			turnDuration: $this->turnDuration,
			startedAt: $this->clock->now(),
			place: $place,
		);

		$this->battleRepository->save($battle);
		$this->messageBus->dispatch(new ResolveBattleTurnMessage($battle->id, 1));

		return $battle;
	}
}
