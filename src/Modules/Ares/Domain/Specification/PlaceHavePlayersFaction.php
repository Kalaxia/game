<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Specification;

use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\Specification;

readonly class PlaceHavePlayersFaction implements Specification
{
	public function __construct(private Player $player)
	{
	}

	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return $this->player->faction->id->equals($candidate->player->faction->id);
	}
}
