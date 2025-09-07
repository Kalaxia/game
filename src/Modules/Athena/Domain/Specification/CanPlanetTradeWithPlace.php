<?php

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Galaxy\Domain\Entity\Planet;

class CanPlanetTradeWithPlace extends PlanetSpecification
{
	/**
	 * @param Planet $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null !== $candidate->player && !$candidate->id->equals($this->planet->id);
	}
}
