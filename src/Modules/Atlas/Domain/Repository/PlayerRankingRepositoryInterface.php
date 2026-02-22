<?php

namespace App\Modules\Atlas\Domain\Repository;

use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\Result;

/**
 * @extends EntityRepositoryInterface<PlayerRanking>
 */
interface PlayerRankingRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<PlayerRanking>
	 */
	public function getFactionPlayerRankings(Ranking $ranking, Color $faction): array;

	public function getPlayerLastRanking(Player $player): ?PlayerRanking;

	public function getBestPlayerRanking(): ?PlayerRanking;

	/**
	 * @return list<PlayerRanking>
	 */
	public function getRankingsByRange(Ranking $ranking, string $field, int $offset, int $limit): array;

	public function getPlayersResources(): Result;

	public function getPlayersResourcesData(): Result;

	public function getPlayersGeneralData(): Result;

	public function getPlayersArmiesData(): Result;

	public function getPlayersPlanetData(): Result;

	public function getPlayersTradeRoutes(): Result;

	public function getPlayersLinkedTradeRoutes(): Result;

	public function getAttackersButcherRanking(): Result;

	public function getDefendersButcherRanking(): Result;
}
