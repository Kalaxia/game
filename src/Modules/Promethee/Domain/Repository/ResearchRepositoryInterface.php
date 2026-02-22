<?php

namespace App\Modules\Promethee\Domain\Repository;

use App\Modules\Promethee\Model\Research;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<Research>
 */
interface ResearchRepositoryInterface extends EntityRepositoryInterface
{
	public function getPlayerResearch(Player $player): ?Research;
}
