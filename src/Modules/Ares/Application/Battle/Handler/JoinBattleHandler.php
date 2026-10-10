<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle\Handler;

use App\Modules\Ares\Application\Battle\JoinBattle;
use App\Modules\Ares\Application\Battle\Message\JoinBattleMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class JoinBattleHandler
{
	public function __construct(
		private JoinBattle $joinBattle,
		private LoggerInterface $logger,
	) {
	}

	public function __invoke(JoinBattleMessage $message): void
	{
		try {
			($this->joinBattle)($message->battleId, $message->fleet);
		} catch (\DomainException $exception) {
			// The battle may have ended before the fleet arrived
			$this->logger->info('Fleet {fleet} could not join battle {battle}: {reason}', [
				'fleet' => $message->fleet['identifier'] ?? '?',
				'battle' => $message->battleId->toRfc4122(),
				'reason' => $exception->getMessage(),
			]);
		}
	}
}
