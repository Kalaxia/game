<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle\Rules;

/**
 * Fleet-size maluses (D10): an officer commands up to a number of PEV growing with their level.
 * Beyond it, the fleet is disorganised: its squadrons lose initiative, dodge and rate of fire.
 */
final readonly class CommandRules
{
	public function __construct(
		public int $basePev = 300,
		public int $pevPerLevel = 100,
		/** Malus per unit of excess ratio: 0.5 means -25% when the fleet is 50% over capacity */
		public float $malusPerExcess = 0.5,
		public float $maxMalus = 0.5,
	) {
	}

	public function getCapacity(int $officerLevel, float $capacityFactor = 1.0): int
	{
		return (int) round(($this->basePev + $this->pevPerLevel * $officerLevel) * $capacityFactor);
	}

	/**
	 * @return float between 0 (no malus) and maxMalus
	 */
	public function getMalus(int $pev, int $officerLevel, float $capacityFactor = 1.0): float
	{
		$capacity = $this->getCapacity($officerLevel, $capacityFactor);
		$excessRatio = max(0, $pev - $capacity) / max(1, $capacity);

		return min($this->maxMalus, $excessRatio * $this->malusPerExcess);
	}
}
