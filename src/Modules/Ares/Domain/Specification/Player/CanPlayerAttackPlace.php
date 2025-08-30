<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Gaia\Domain\Entity\Place;

class CanPlayerAttackPlace extends PlayerSpecification
{
	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return (null !== $candidate->player && !$candidate->player->faction->id->equals($this->player->faction->id))
			|| (null === $candidate->player && 1 === $candidate->typeOfPlace);
	}
}
