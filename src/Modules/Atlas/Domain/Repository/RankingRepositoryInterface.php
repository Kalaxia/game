<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Domain\Repository;

use App\Modules\Atlas\Model\Ranking;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<Ranking>
 */
interface RankingRepositoryInterface extends EntityRepositoryInterface
{
	public function getLastRanking(): ?Ranking;
}
