<?php

namespace App\Modules\Ares\Domain\Specification;

use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Enum\PlaceType;
use App\Shared\Domain\Specification\Specification;

class PlaceIsInhabited implements Specification
{
	public function isGeneralizationOf(Specification $specification): bool
	{
		if ($specification instanceof PlaceHasPlayer) {
			return true;
		}

		return false;
	}

	public function isSpecialCaseOf(Specification $specification): bool
	{
		return false;
	}

	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return PlaceType::Planet === $candidate->typeOfPlace;
	}
}
