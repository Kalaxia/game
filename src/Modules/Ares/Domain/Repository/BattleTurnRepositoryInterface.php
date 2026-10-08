<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Repository;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleTurn;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<BattleTurn>
 */
interface BattleTurnRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<BattleTurn> in turn order
	 */
	public function getBattleTurns(Battle $battle): array;
}
