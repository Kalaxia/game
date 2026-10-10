<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

/**
 * Traits of the officer leading a fleet, in the style of Total War Saga (Q13, D13).
 *
 * An officer has 1 to 3 traits. Effects are described in docs/fleet-combat/content-proposal.md and
 * applied where the engine handles the matching mechanic (targeting, fire, morale, command capacity).
 */
enum OfficerTrait: string
{
	/** +10% initiative, withdraws at 20 morale, charges the closest enemy (fires on preferred targets in range) */
	case Aggressive = 'aggressive';
	/** Withdraws at 40 morale, +15% dodge while withdrawn or fleeing, exposed 1 turn when fleeing */
	case Cautious = 'cautious';
	/** Morale losses -25% */
	case Tenacious = 'tenacious';
	/** Allied fleets next to it recover +2 morale per turn */
	case Charismatic = 'charismatic';
	/** Command capacity +20% */
	case Logistician = 'logistician';
	/** Served first when actions are handed out among fleets */
	case Tactician = 'tactician';
	/** Prefers destroyers and cruisers, +10% damage against them */
	case CapitalHunter = 'capital_hunter';
	/** +10% defence when its side defends */
	case Defender = 'defender';
	/** +10% attack, -10% dodge */
	case Reckless = 'reckless';
	/** -15% initiative, remobilises at 60 morale */
	case Indecisive = 'indecisive';
	/** +10% damage against fleets already engaged by allies, morale recovery near allies halved */
	case Butcher = 'butcher';
	/** Being outnumbered never takes its morale below 20 */
	case Veteran = 'veteran';

	public const int MAX_PER_OFFICER = 3;
}
