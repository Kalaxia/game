<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Gaia\Domain\Entity\Planet;

class CanRecycle extends PlayerSpecification
{
	/**
	 * @param Planet $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null === $candidate->system->sector->faction
			|| $candidate->system->sector->faction->id->equals($this->player->faction->id);
	}
}
