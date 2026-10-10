<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\CombatSimulator;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * Real-time battles started by the visitor in the combat simulator. Only their creator may watch
 * them or send reinforcements (Q17: participants only).
 */
readonly class SimulatedBattleStorage
{
	private const string SESSION_KEY = 'combat_simulator.battles';
	private const int MAX_BATTLES = 20;

	public function __construct(
		private RequestStack $requestStack,
	) {
	}

	public function add(Uuid $battleId): void
	{
		$battles = [...$this->all(), $battleId->toRfc4122()];

		$this->requestStack->getSession()->set(self::SESSION_KEY, array_slice($battles, -self::MAX_BATTLES));
	}

	public function has(Uuid $battleId): bool
	{
		return in_array($battleId->toRfc4122(), $this->all(), true);
	}

	/**
	 * @return list<string>
	 */
	private function all(): array
	{
		return array_values($this->requestStack->getSession()->get(self::SESSION_KEY, []));
	}
}
