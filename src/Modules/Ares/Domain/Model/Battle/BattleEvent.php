<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;

/**
 * Something that happened during a turn. The event log drives the animation, the report and the replay.
 */
final readonly class BattleEvent
{
	public const string FLEET_JOINED = 'fleet_joined';
	public const string FLEET_MOVED = 'fleet_moved';
	public const string FLEET_DESTROYED = 'fleet_destroyed';
	public const string SQUADRON_MOVED = 'squadron_moved';
	public const string SQUADRON_FIRED = 'squadron_fired';
	public const string SQUADRON_DESTROYED = 'squadron_destroyed';
	public const string SQUADRON_SPLASHED = 'squadron_splashed';
	public const string BATTLE_ENDED = 'battle_ended';
	public const string ENGAGEMENT_DELAYED = 'engagement_delayed';
	public const string ENGAGEMENT_STARTED = 'engagement_started';
	public const string FLEET_WITHDREW = 'fleet_withdrew';
	public const string FLEET_REMOBILISED = 'fleet_remobilised';
	public const string FLEET_FLEEING = 'fleet_fleeing';
	public const string FLEET_FLED = 'fleet_fled';
	public const string MORALE_UPDATED = 'morale_updated';

	/**
	 * @param array<string, mixed> $payload
	 */
	private function __construct(
		public string $type,
		public array $payload,
	) {
	}

	public static function fleetJoined(BattleFleet $fleet): self
	{
		return new self(self::FLEET_JOINED, [
			'fleet' => $fleet->identifier,
			'side' => $fleet->side,
			'cluster' => self::hex($fleet->cluster),
			'orientation' => $fleet->orientation->value,
			'squadrons' => array_map(fn (BattleSquadron $squadron) => [
				'squadron' => $squadron->identifier,
				'type' => $squadron->type->identifier,
				'ships' => $squadron->ships,
				'cell' => self::hex($squadron->cell),
			], $fleet->squadrons),
		]);
	}

	/**
	 * @param list<BattleSquadron> $squadrons squadrons moved along with the fleet area
	 */
	public static function fleetMoved(BattleFleet $fleet, Hex $from, Hex $translation, array $squadrons): self
	{
		return new self(self::FLEET_MOVED, [
			'fleet' => $fleet->identifier,
			'from' => self::hex($from),
			'to' => self::hex($fleet->cluster),
			'translation' => self::hex($translation),
			'squadrons' => array_map(fn (BattleSquadron $squadron) => $squadron->identifier, $squadrons),
		]);
	}

	public static function fleetDestroyed(BattleFleet $fleet): self
	{
		return new self(self::FLEET_DESTROYED, ['fleet' => $fleet->identifier]);
	}

	public static function squadronMoved(BattleSquadron $squadron, Hex $from): self
	{
		return new self(self::SQUADRON_MOVED, [
			'squadron' => $squadron->identifier,
			'from' => self::hex($from),
			'to' => self::hex($squadron->cell),
		]);
	}

	public static function squadronFired(BattleSquadron $attacker, BattleSquadron $target, int $shots, int $hits, int $damage, int $kills): self
	{
		return new self(self::SQUADRON_FIRED, [
			'attacker' => $attacker->identifier,
			'target' => $target->identifier,
			'shots' => $shots,
			'hits' => $hits,
			'damage' => $damage,
			'kills' => $kills,
		]);
	}

	/**
	 * Area damage of a shot, dealt to a squadron next to the target (Q12).
	 */
	public static function squadronSplashed(BattleSquadron $attacker, BattleSquadron $victim, int $damage, int $kills): self
	{
		return new self(self::SQUADRON_SPLASHED, [
			'attacker' => $attacker->identifier,
			'target' => $victim->identifier,
			'damage' => $damage,
			'kills' => $kills,
		]);
	}

	public static function squadronDestroyed(BattleSquadron $squadron): self
	{
		return new self(self::SQUADRON_DESTROYED, ['squadron' => $squadron->identifier]);
	}

	public static function engagementDelayed(int $untilTurn): self
	{
		return new self(self::ENGAGEMENT_DELAYED, ['until_turn' => $untilTurn]);
	}

	public static function engagementStarted(): self
	{
		return new self(self::ENGAGEMENT_STARTED, []);
	}

	public static function fleetWithdrew(BattleFleet $fleet): self
	{
		return new self(self::FLEET_WITHDREW, ['fleet' => $fleet->identifier, 'morale' => (int) round($fleet->morale)]);
	}

	public static function fleetRemobilised(BattleFleet $fleet): self
	{
		return new self(self::FLEET_REMOBILISED, ['fleet' => $fleet->identifier, 'morale' => (int) round($fleet->morale)]);
	}

	public static function fleetFleeing(BattleFleet $fleet): self
	{
		return new self(self::FLEET_FLEEING, ['fleet' => $fleet->identifier, 'morale' => (int) round($fleet->morale)]);
	}

	/**
	 * The fleet leaves the battle with its surviving squadrons.
	 */
	public static function fleetFled(BattleFleet $fleet): self
	{
		return new self(self::FLEET_FLED, [
			'fleet' => $fleet->identifier,
			'squadrons' => array_map(fn (BattleSquadron $squadron) => $squadron->identifier, $fleet->getActiveSquadrons()),
		]);
	}

	/**
	 * @param list<BattleFleet> $fleets
	 */
	public static function moraleUpdated(array $fleets): self
	{
		$morale = [];
		foreach ($fleets as $fleet) {
			$morale[$fleet->identifier] = (int) round($fleet->morale);
		}

		return new self(self::MORALE_UPDATED, ['morale' => $morale]);
	}

	public static function battleEnded(?int $winningSide): self
	{
		return new self(self::BATTLE_ENDED, ['winning_side' => $winningSide]);
	}

	/**
	 * @return array{type: string}&array<string, mixed>
	 */
	public function toArray(): array
	{
		return ['type' => $this->type] + $this->payload;
	}

	/**
	 * @return array{q: int, r: int}|null
	 */
	private static function hex(?Hex $hex): ?array
	{
		return null !== $hex ? ['q' => $hex->q, 'r' => $hex->r] : null;
	}
}
