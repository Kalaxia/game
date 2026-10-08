<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Uid\Uuid;

/**
 * Serialises the changes of a running battle: the resolution of a turn and the arrival of a fleet
 * both read and write its whole state.
 */
readonly class BattleLock
{
	public function __construct(
		private LockFactory $lockFactory,
	) {
	}

	/**
	 * @template T
	 *
	 * @param callable(): T $callback
	 *
	 * @return T
	 */
	public function run(Uuid $battleId, callable $callback): mixed
	{
		$lock = $this->lockFactory->createLock(sprintf('battle-%s', $battleId->toRfc4122()), ttl: 30);
		$lock->acquire(true);

		try {
			return $callback();
		} finally {
			$lock->release();
		}
	}
}
