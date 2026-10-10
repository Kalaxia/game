<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\Rules\MoraleRules;

/**
 * Effects of the officer traits (Q13), gathered in one place so that every mechanic asks the same rules.
 */
final class OfficerTraitEffects
{
	public static function getInitiativeFactor(BattleFleet $fleet): float
	{
		return 1.0
			+ ($fleet->hasTrait(OfficerTrait::Aggressive) ? 0.1 : 0.0)
			- ($fleet->hasTrait(OfficerTrait::Indecisive) ? 0.15 : 0.0);
	}

	public static function getCommandCapacityFactor(BattleFleet $fleet): float
	{
		return $fleet->hasTrait(OfficerTrait::Logistician) ? 1.2 : 1.0;
	}

	/**
	 * An aggressive officer sends their squadrons toward the closest enemy, whatever its ship class.
	 * In range, they still fire on their preferred targets: ignoring the counters wasted too many shots.
	 */
	public static function ignoresTargetPreferences(BattleFleet $fleet): bool
	{
		return $fleet->hasTrait(OfficerTrait::Aggressive);
	}

	/**
	 * A capital hunter's heavy hitters (bombers, artillery, capital ships) fire first on destroyers and
	 * cruisers in range. Other squadrons keep their own targets: light guns barely scratch a destroyer,
	 * and making them chase heavy ships, or switch to them in range, lost most battles in simulations.
	 * Movement is unchanged.
	 *
	 * @return list<ShipClass>
	 */
	public static function getPreferredTargets(BattleSquadron $squadron, bool $inRangeOnly = true): array
	{
		if (!$inRangeOnly || !$squadron->getFleet()->hasTrait(OfficerTrait::CapitalHunter) || !in_array($squadron->type->role, [ShipRole::Bomber, ShipRole::Artillery, ShipRole::Capital], true)) {
			return $squadron->type->preferredTargets;
		}

		return [ShipClass::Destroyer, ...array_values(array_filter($squadron->type->preferredTargets, fn (ShipClass $class) => ShipClass::Destroyer !== $class))];
	}

	public static function getAttackFactor(BattleFleet $fleet): float
	{
		return $fleet->hasTrait(OfficerTrait::Reckless) ? 1.1 : 1.0;
	}

	public static function getDamageFactor(BattleState $state, BattleSquadron $shooter, BattleSquadron $target): float
	{
		$fleet = $shooter->getFleet();
		$factor = 1.0;

		if ($fleet->hasTrait(OfficerTrait::CapitalHunter) && ShipClass::Destroyer === $target->type->class) {
			$factor += 0.1;
		}

		if ($fleet->hasTrait(OfficerTrait::Butcher) && self::isEngagedByAllies($state, $fleet, $target->getFleet())) {
			$factor += 0.1;
		}

		return $factor;
	}

	/**
	 * Applies to the dodge of the squadrons of the fleet.
	 */
	public static function getDodgeFactor(BattleFleet $fleet): float
	{
		$factor = $fleet->hasTrait(OfficerTrait::Reckless) ? 0.9 : 1.0;

		if ($fleet->hasTrait(OfficerTrait::Cautious) && in_array($fleet->status, [FleetStatus::Withdrawn, FleetStatus::Fleeing], true)) {
			$factor *= 1.15;
		}

		return $factor;
	}

	public static function getDefenseFactor(BattleState $state, BattleFleet $fleet): float
	{
		$defendingSide = $state->rules->engagement?->defendingSide;

		return $fleet->hasTrait(OfficerTrait::Defender) && $fleet->side === $defendingSide ? 1.1 : 1.0;
	}

	public static function getWithdrawalThreshold(BattleFleet $fleet, MoraleRules $rules): float
	{
		return match (true) {
			$fleet->hasTrait(OfficerTrait::Aggressive) => $rules->withdrawalThreshold - 10,
			$fleet->hasTrait(OfficerTrait::Cautious) => $rules->withdrawalThreshold + 10,
			default => $rules->withdrawalThreshold,
		};
	}

	public static function getRemobilisationThreshold(BattleFleet $fleet, MoraleRules $rules): float
	{
		return $rules->remobilisationThreshold + ($fleet->hasTrait(OfficerTrait::Indecisive) ? 10 : 0);
	}

	public static function getExposedTurns(BattleFleet $fleet, MoraleRules $rules): int
	{
		return $fleet->hasTrait(OfficerTrait::Cautious) ? max(1, $rules->exposedTurns - 1) : $rules->exposedTurns;
	}

	public static function getLossFactor(BattleFleet $fleet, MoraleRules $rules): float
	{
		return $rules->lossFactor * ($fleet->hasTrait(OfficerTrait::Tenacious) ? 0.75 : 1.0);
	}

	/**
	 * Recovery due to the allied fleets next to this one: the usual bonus (halved for a butcher,
	 * feared by their own), plus 2 for each charismatic officer nearby.
	 */
	public static function getAlliedRecovery(BattleState $state, BattleFleet $fleet, MoraleRules $rules): float
	{
		$allies = array_filter(
			$state->getActiveFleets(),
			fn (BattleFleet $ally) => $ally !== $fleet && $ally->side === $fleet->side && 1 === $ally->cluster->distanceTo($fleet->cluster),
		);

		if ([] === $allies) {
			return 0.0;
		}

		$charismaticAllies = count(array_filter($allies, fn (BattleFleet $ally) => $ally->hasTrait(OfficerTrait::Charismatic)));

		return $rules->alliedRecovery * ($fleet->hasTrait(OfficerTrait::Butcher) ? 0.5 : 1.0) + 2.0 * $charismaticAllies;
	}

	/**
	 * Morale under which being outnumbered no longer lowers it.
	 */
	public static function getOutnumberedMoraleFloor(BattleFleet $fleet): float
	{
		return $fleet->hasTrait(OfficerTrait::Veteran) ? 20.0 : 0.0;
	}

	/**
	 * Whether another fleet of the same side as the shooter stands next to the target fleet.
	 */
	private static function isEngagedByAllies(BattleState $state, BattleFleet $shooter, BattleFleet $target): bool
	{
		foreach ($state->getActiveFleets() as $ally) {
			if ($ally !== $shooter && $ally->side === $shooter->side && 1 === $ally->cluster->distanceTo($target->cluster)) {
				return true;
			}
		}

		return false;
	}
}
