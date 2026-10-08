<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use Symfony\Component\Uid\Uuid;

/**
 * A battle running in real time (Phase 6): its state between two turns is stored, and a turn
 * is resolved every turn duration by ResolveBattleTurnHandler. The events of each turn are kept
 * as BattleTurn for the live viewer, the replay and the report.
 *
 * The state is stored as a PHP serialization of the BattleState object graph: a battle lasts
 * minutes to an hour, and the engine model is free to change between two releases.
 */
class Battle
{
	/** @var string|resource serialized BattleState, a stream once loaded from the database */
	private mixed $state;

	public function __construct(
		public Uuid $id,
		BattleState $state,
		/** Real duration of a turn, in seconds (Q6) */
		public int $turnDuration,
		public \DateTimeImmutable $startedAt,
		public int $turn = 0,
		public ?\DateTimeImmutable $endedAt = null,
		public ?int $winningSide = null,
	) {
		$this->setState($state);
	}

	public function getState(): BattleState
	{
		$state = unserialize(is_resource($this->state) ? (string) stream_get_contents($this->state, -1, 0) : $this->state);

		if (!$state instanceof BattleState) {
			throw new \UnexpectedValueException(sprintf('The state of battle %s cannot be restored', $this->id));
		}

		return $state;
	}

	public function setState(BattleState $state): void
	{
		$this->state = serialize($state);
		$this->turn = $state->turn;
		$this->winningSide = $state->winningSide;
	}

	public function end(\DateTimeImmutable $endedAt): void
	{
		$this->endedAt = $endedAt;
	}

	public function isOver(): bool
	{
		return null !== $this->endedAt;
	}

	/**
	 * Time at which a turn is due. The first turn is resolved as soon as the battle starts,
	 * so that the deployment of the fleets is shown at once.
	 */
	public function getTurnDueAt(int $turn): \DateTimeImmutable
	{
		return $this->startedAt->modify(sprintf('+%d seconds', ($turn - 1) * $this->turnDuration));
	}
}
