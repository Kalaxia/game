<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle\Handler;

use App\Modules\Ares\Application\Battle\BattleLock;
use App\Modules\Ares\Application\Battle\BattleUpdatePublisherInterface;
use App\Modules\Ares\Application\Battle\Message\ResolveBattleTurnMessage;
use App\Modules\Ares\Domain\Model\Battle\BattleTurn;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Ares\Domain\Repository\BattleTurnRepositoryInterface;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * The battle ticker: resolves one turn, stores its events, pushes them to the viewers,
 * then schedules the next turn at its due time (start + (turn - 1) × turn duration),
 * so that slow turns do not shift the following ones.
 */
#[AsMessageHandler]
readonly class ResolveBattleTurnHandler
{
	public function __construct(
		private BattleRepositoryInterface $battleRepository,
		private BattleTurnRepositoryInterface $battleTurnRepository,
		private BattleEngine $battleEngine,
		private BattleUpdatePublisherInterface $battleUpdatePublisher,
		private BattleLock $battleLock,
		private MessageBusInterface $messageBus,
		private ClockInterface $clock,
	) {
	}

	public function __invoke(ResolveBattleTurnMessage $message): void
	{
		$battle = $this->battleLock->run($message->battleId, function () use ($message) {
			if (null === ($battle = $this->battleRepository->get($message->battleId))) {
				return null;
			}
			$this->battleRepository->refresh($battle);

			// Already resolved, or out of order: ignored
			if ($battle->isOver() || $battle->turn + 1 !== $message->turn) {
				return null;
			}

			$state = $battle->getState();
			$result = $this->battleEngine->resolveTurn($state);
			$now = $this->clock->now();

			$battle->setState($state);
			if ($state->isOver) {
				$battle->end($now);
			}
			$this->battleTurnRepository->save(BattleTurn::fromResult($battle, $result, $now));
			$this->battleRepository->save($battle);
			$this->battleUpdatePublisher->publishTurn($battle, $state, $result);

			return $battle;
		});

		if (null === $battle || $battle->isOver()) {
			return;
		}

		$nextTurn = $battle->turn + 1;
		$delay = $battle->getTurnDueAt($nextTurn)->format('Uv') - $this->clock->now()->format('Uv');
		$this->messageBus->dispatch(new ResolveBattleTurnMessage($battle->id, $nextTurn), [new DelayStamp(max(0, (int) $delay))]);
	}
}
