<?php

namespace App\Modules\Atlas\Domain\Repository;

use App\Modules\Atlas\Model\FactionRanking;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<FactionRanking>
 */
interface FactionRankingRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return array{nb: int, income: int}
	 */
	public function getRoutesIncome(Color $faction): array;

	public function getLastRanking(Color $faction): ?FactionRanking;

	/**
	 * @return list<FactionRanking>
	 */
	public function getRankingsByField(Ranking $ranking, string $field): array;

	/**
	 * @return list<FactionRanking>
	 */
	public function getFactionRankings(Color $faction): array;
}
