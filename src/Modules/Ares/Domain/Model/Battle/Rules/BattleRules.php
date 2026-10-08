<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle\Rules;

/**
 * Optional rules of a battle. A rule left to null is disabled.
 */
final readonly class BattleRules
{
	public function __construct(
		public ?CommandRules $command = null,
		public ?EngagementRules $engagement = null,
		public ?MoraleRules $morale = null,
	) {
	}
}
