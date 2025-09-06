<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;

class CanPlayerMoveToPlace extends PlayerSpecification
{
	public function __construct(Player $player, protected Place $planet)
	{
		parent::__construct($player);
	}

	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		$player = $candidate->player;

		return null !== $player
			&& (($player->id === $this->player->id && !$candidate->id->equals($this->planet->id))
			|| $player->faction->id->equals($this->player->faction->id));
	}
}
