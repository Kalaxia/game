<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle\Rules;

/**
 * Morale and retreat (D12). Morale goes from 0 to 100.
 */
final readonly class MoraleRules
{
	public function __construct(
		/** Morale lost per percent of the fleet's initial PEV lost in a turn */
		public float $lossFactor = 1.2,
		/** Morale gained per percent of the fleet's initial PEV destroyed among the enemies in a turn */
		public float $victoryFactor = 0.5,
		/** Morale lost per turn while the side is outnumbered, at most twice this value */
		public float $outnumberedPenalty = 0.75,
		/** Morale recovered in a turn without losses */
		public float $recovery = 1.0,
		/** Additional recovery next to an allied fleet */
		public float $alliedRecovery = 2.0,
		public float $withdrawalThreshold = 30.0,
		public float $flightThreshold = 15.0,
		public float $remobilisationThreshold = 50.0,
		/** Turns a fleeing fleet stays exposed to enemy fire before leaving the battle */
		public int $exposedTurns = 2,
	) {
	}
}
