<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Enum\PlaceType;

class CanPlayerAttackPlace extends PlayerSpecification
{
	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		$player = $candidate->player;

		return (null !== $player && !$player->faction->id->equals($this->player->faction->id))
			|| (null === $player && PlaceType::Planet === $candidate->getType());
	}
}
