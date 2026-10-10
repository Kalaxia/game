<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service;

use App\Modules\Ares\Model\Commander;

/**
 * Strength of a fleet, in PEV: the sum of its squadrons.
 */
readonly class GetCommanderPev
{
	public function __construct(private GetSquadronPev $getSquadronPev)
	{
	}

	public function __invoke(Commander $commander): int
	{
		$pev = 0;

		foreach ($commander->squadrons as $squadron) {
			$pev += ($this->getSquadronPev)($squadron);
		}

		return $pev;
	}
}
