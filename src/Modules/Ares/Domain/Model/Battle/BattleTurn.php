<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use Symfony\Component\Uid\Uuid;

/**
 * Events of one resolved turn of a stored battle, as given by TurnResult::toArray().
 */
class BattleTurn
{
	/**
	 * @param list<array<string, mixed>> $events
	 */
	public function __construct(
		public Uuid $id,
		public Battle $battle,
		public int $turn,
		public array $events,
		public \DateTimeImmutable $resolvedAt,
	) {
	}

	public static function fromResult(Battle $battle, TurnResult $result, \DateTimeImmutable $resolvedAt): self
	{
		return new self(Uuid::v7(), $battle, $result->turn, $result->toArray()['events'], $resolvedAt);
	}

	public function toResult(): TurnResult
	{
		return new TurnResult($this->turn, array_map(BattleEvent::fromArray(...), $this->events));
	}

	/**
	 * @return array{turn: int, events: list<array<string, mixed>>}
	 */
	public function toArray(): array
	{
		return ['turn' => $this->turn, 'events' => $this->events];
	}
}
