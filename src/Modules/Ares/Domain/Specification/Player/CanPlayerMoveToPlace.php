<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Zeus\Model\Player;

class CanPlayerMoveToPlace extends PlayerSpecification
{
	public function __construct(Player $player, protected OrbitalBase $orbitalBase)
	{
		parent::__construct($player);
	}

	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null !== $candidate->player
			&& (($candidate->player->id === $this->player->id && !$candidate->id->equals($this->orbitalBase->place->id))
			|| $candidate->player->faction->id->equals($this->player->faction->id));
	}
}
