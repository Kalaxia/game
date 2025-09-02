<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Enum\PlaceType;

class CanPlayerAttackPlace extends PlayerSpecification
{
	/**
	 * @param Planet $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		$player = $candidate->player;

		return (null !== $player && !$player->faction->id->equals($this->player->faction->id))
			|| (null === $player && PlaceType::Planet === $candidate->getType());
	}
}
