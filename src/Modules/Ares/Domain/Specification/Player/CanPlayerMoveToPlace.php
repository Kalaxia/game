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
		$player = $candidate->base?->player;

		return null !== $player
			&& (($player->id === $this->player->id && !$candidate->id->equals($this->orbitalBase->place->id))
			|| $player->faction->id->equals($this->player->faction->id));
	}
}
