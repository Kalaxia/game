<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle\Report;

/**
 * Post-battle report built from the event log (Q16): per side, per fleet and per squadron figures,
 * and the key moments of the battle.
 */
final readonly class BattleReport
{
	public const string HIGHLIGHT_FIRST_BLOOD = 'first_blood';
	public const string HIGHLIGHT_DEADLIEST_ACTION = 'deadliest_action';
	public const string HIGHLIGHT_REINFORCEMENT = 'reinforcement';
	public const string HIGHLIGHT_FLEET_DESTROYED = 'fleet_destroyed';
	public const string HIGHLIGHT_TURNING_POINT = 'turning_point';
	public const string HIGHLIGHT_MOST_VALUABLE_SQUADRON = 'most_valuable_squadron';

	/**
	 * @param array<int, array{initial_pev: int, lost_pev: int, ships_lost: int, damage_dealt: int}> $sides      by side
	 * @param list<array<string, mixed>>                                                             $fleets
	 * @param list<SquadronReport>                                                                   $squadrons
	 * @param list<array{type: string, turn: int}&array<string, mixed>>                              $highlights in turn order
	 */
	public function __construct(
		public int $turns,
		public ?int $winningSide,
		public array $sides,
		public array $fleets,
		public array $squadrons,
		public array $highlights,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'turns' => $this->turns,
			'winning_side' => $this->winningSide,
			'sides' => $this->sides,
			'fleets' => $this->fleets,
			'squadrons' => array_map(fn (SquadronReport $squadron) => $squadron->toArray(), $this->squadrons),
			'highlights' => $this->highlights,
		];
	}
}
