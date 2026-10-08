<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

/**
 * Behaviour of a fleet in battle (D12).
 */
enum FleetStatus: string
{
	/** Fights normally */
	case Engaged = 'engaged';
	/** Withdrawn behind allied fleets, does not fire, may remobilise */
	case Withdrawn = 'withdrawn';
	/** Leaving the battle: does not fire and stays exposed for a few turns */
	case Fleeing = 'fleeing';
	/** Has left the battle with its surviving ships */
	case Fled = 'fled';
}
