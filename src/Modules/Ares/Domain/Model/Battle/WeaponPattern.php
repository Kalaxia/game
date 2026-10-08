<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

/**
 * Cells a weapon can hit, relative to the squadron (Q12, docs/fleet-combat/content-proposal.md).
 *
 * Squadrons turn freely toward their target (maintainer's decision, 2026-10-08), so the direction of
 * the frontal cone and the broadside does not restrict fire yet: only the ranges and the area damage do.
 * The directions are kept in the data for when turning gets a cost.
 */
enum WeaponPattern: string
{
	/** Adjacent cells, any direction */
	case Close = 'close';
	/** Cells in range in the 3 directions facing the target */
	case FrontalCone = 'frontal_cone';
	/** Cells in range on both sides */
	case Broadside = 'broadside';
	/** Cells between the minimum and maximum range, over allied squadrons */
	case Ring = 'ring';
	/** Like a ring; the shot also hits the squadrons next to the target */
	case Area = 'area';
}
