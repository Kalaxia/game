<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Battle;

use App\Modules\Ares\Application\Battle\BattleUpdatePublisherInterface;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\BattleTurn;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;
use App\Modules\Ares\Domain\Repository\BattleTurnRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;

/**
 * Publishes each turn as a private Mercure update: only subscribers authorised for the topic
 * of the battle receive it (Q17: participants only).
 */
readonly class MercureBattleUpdatePublisher implements BattleUpdatePublisherInterface
{
	public function __construct(
		private HubInterface $hub,
		private PresentBattle $presentBattle,
		private BattleTurnRepositoryInterface $battleTurnRepository,
		private LoggerInterface $logger,
	) {
	}

	public static function getTopic(Uuid $battleId): string
	{
		return sprintf('/battles/%s', $battleId->toRfc4122());
	}

	public function publishTurn(Battle $battle, BattleState $state, TurnResult $turn): void
	{
		// The report of a finished battle is built from all its turns
		$turns = $state->isOver
			? array_map(fn (BattleTurn $battleTurn) => $battleTurn->toResult(), $this->battleTurnRepository->getBattleTurns($battle))
			: [];

		try {
			$this->hub->publish(new Update(
				self::getTopic($battle->id),
				(string) json_encode($this->presentBattle->presentTurn($state, $turn, $turns)),
				private: true,
			));
		} catch (\Throwable $exception) {
			// The battle goes on without live viewers: they catch up from the stored turns
			$this->logger->warning('Battle {battle}: turn {turn} could not be published: {reason}', [
				'battle' => $battle->id->toRfc4122(),
				'turn' => $turn->turn,
				'reason' => $exception->getMessage(),
			]);
		}
	}
}
