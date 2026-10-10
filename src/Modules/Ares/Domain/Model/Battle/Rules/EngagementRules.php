<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle\Rules;

/**
 * Engagement delay (D11): when the attackers are outnumbered, nobody fires for a while,
 * which leaves time to deploy and for reinforcements to arrive.
 */
final readonly class EngagementRules
{
	public function __construct(
		/** Side already in orbit; every other side attacks */
		public int $defendingSide = 0,
		public int $delayTurns = 18,
		/** Attackers are outnumbered below this ratio of the defenders' PEV */
		public float $minForceRatio = 0.5,
	) {
	}
}
