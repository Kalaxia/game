<?php

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Gaia\Domain\Entity\Place;

class CanOrbitalBaseTradeWithPlace extends OrbitalBaseSpecification
{
	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null !== $candidate->base?->player && !$candidate->id->equals($this->orbitalBase->place->id);
	}
}
