<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Galaxy\Domain\Entity\Place;

class CanRecycle extends PlayerSpecification
{
	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null === $candidate->system->sector->faction
			|| $candidate->system->sector->faction->id->equals($this->player->faction->id);
	}
}
