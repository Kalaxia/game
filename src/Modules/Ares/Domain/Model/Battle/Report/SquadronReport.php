<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle\Report;

/**
 * What a squadron did during a battle.
 */
final class SquadronReport
{
	public int $damageDealt = 0;
	public int $damageTaken = 0;
	public int $kills = 0;
	public int $shots = 0;
	public int $hits = 0;
	public ?int $destroyedAtTurn = null;

	public function __construct(
		public readonly string $squadron,
		public readonly string $fleet,
		public readonly string $type,
		public readonly int $initialShips,
		public int $remainingShips,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'squadron' => $this->squadron,
			'fleet' => $this->fleet,
			'type' => $this->type,
			'initial_ships' => $this->initialShips,
			'remaining_ships' => $this->remainingShips,
			'damage_dealt' => $this->damageDealt,
			'damage_taken' => $this->damageTaken,
			'kills' => $this->kills,
			'shots' => $this->shots,
			'hits' => $this->hits,
			'destroyed_at_turn' => $this->destroyedAtTurn,
		];
	}
}
