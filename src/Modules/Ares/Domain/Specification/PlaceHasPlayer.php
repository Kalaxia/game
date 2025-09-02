<?php

namespace App\Modules\Ares\Domain\Specification;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Shared\Domain\Specification\Specification;

class PlaceHasPlayer implements Specification
{
	public function isGeneralizationOf(Specification $specification): bool
	{
		return false;
	}

	public function isSpecialCaseOf(Specification $specification): bool
	{
		if ($specification instanceof PlaceIsInhabited) {
			return true;
		}

		return false;
	}

	/**
	 * @param Planet $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null !== $candidate?->player;
	}
}
