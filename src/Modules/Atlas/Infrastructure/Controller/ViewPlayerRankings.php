<?php

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewPlayerRankings extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlayerManager $playerManager,
		PlayerRepositoryInterface $playerRepository,
		PlayerRankingRepositoryInterface $playerRankingRepository,
		RankingRepositoryInterface $rankingRepository,
	): Response {
		// load current player
		$p = $playerRankingRepository->getPlayerLastRanking($currentPlayer);

		// TDO refactor with property accessor
		$positionGetter = fn (PlayerRanking|null $p, callable $positionFieldGetter) => (
			null === $p ||
			'top' === $request->query->get('mode') ||
			$positionFieldGetter($p) - PlayerRanking::PREV < 0
		) ? 0 : $positionFieldGetter($p) - PlayerRanking::PREV;

		$generalPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->generalPosition);
		$resourcesPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->resourcesPosition);
		$experiencePosition = $positionGetter($p, fn (PlayerRanking $p) => $p->experiencePosition);
		$fightPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->fightPosition);
		$armiesPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->armiesPosition);
		$butcherPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->butcherPosition);
		$traderPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->traderPosition);

		$ranking = $rankingRepository->getLastRanking();



		return $this->render(
			'pages/atlas/player_rankings.html.twig',
			null !== $ranking
				? [
					'has_ranking' => true,
					'best_player' => $playerRankingRepository->getBestPlayerRanking()?->player,
					// TODO Refactor with a closure
					'general_rankings' => $playerRankingRepository->getRankingsByRange($ranking, 'generalPosition', $generalPosition, PlayerRanking::STEP),
					'experience_rankings' => $playerRankingRepository->getRankingsByRange($ranking, 'experiencePosition', $experiencePosition, PlayerRanking::STEP),
					'fight_rankings' => $playerRankingRepository->getRankingsByRange($ranking, 'fightPosition', $fightPosition, PlayerRanking::STEP),
					'resources_rankings' => $playerRankingRepository->getRankingsByRange($ranking, 'resourcesPosition', $resourcesPosition, PlayerRanking::STEP),
					'armies_rankings' => $playerRankingRepository->getRankingsByRange($ranking, 'armiesPosition', $armiesPosition, PlayerRanking::STEP),
					'butcher_rankings' => $playerRankingRepository->getRankingsByRange($ranking, 'butcherPosition', $butcherPosition, PlayerRanking::STEP),
					'trader_rankings' => $playerRankingRepository->getRankingsByRange($ranking, 'traderPosition', $traderPosition, PlayerRanking::STEP),
					'active_players_count' => $playerRepository->countActivePlayers(),
					'all_players_count' => $playerRepository->countAllPlayers(),
				] : [
					'has_ranking' => false,
				],
		);
	}
}
