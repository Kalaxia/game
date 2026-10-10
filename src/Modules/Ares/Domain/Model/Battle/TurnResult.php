<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

final readonly class TurnResult
{
	/**
	 * @param list<BattleEvent> $events
	 */
	public function __construct(
		public int $turn,
		public array $events,
	) {
	}

	public function hasEndedBattle(): bool
	{
		foreach ($this->events as $event) {
			if (BattleEvent::BATTLE_ENDED === $event->type) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array{turn: int, events: list<array<string, mixed>>}
	 */
	public function toArray(): array
	{
		return [
			'turn' => $this->turn,
			'events' => array_map(fn (BattleEvent $event) => $event->toArray(), $this->events),
		];
	}
}
