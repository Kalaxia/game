<?php

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Gaia\Domain\Entity\Place;

class CanPlanetTradeWithPlace extends PlanetSpecification
{
	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null !== $candidate->base?->player && !$candidate->id->equals($this->planet->place->id);
	}
}
