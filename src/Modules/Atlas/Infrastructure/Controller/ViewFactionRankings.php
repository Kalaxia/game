<?php

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Atlas\Repository\RankingRepository;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewFactionRankings extends AbstractController
{
	public function __invoke(
		ClockInterface $clock,
		DurationHandler $durationHandler,
		FactionRankingRepositoryInterface $factionRankingRepository,
		RankingRepository $rankingRepository,
	): Response {
		// @TODO Replace this parameter by a dynamic field
		$serverStartTime = new \DateTimeImmutable($this->getParameter('server_start_time'));
		$hoursBeforeRankingStart = intval($this->getParameter('hours_before_start_of_ranking'));

		$ranking = $rankingRepository->getLastRanking();

		if (null !== $ranking) {
			$pointsRankings = $factionRankingRepository->getRankingsByField($ranking, 'pointsPosition');
			$generalRankings = $factionRankingRepository->getRankingsByField($ranking, 'generalPosition');
		}

		return $this->render(
			'pages/atlas/faction_rankings.html.twig',
			null !== $ranking
				? [
					'has_ranking' => true,
					'best_faction' => ($durationHandler->getHoursDiff($serverStartTime, $clock->now()) > $hoursBeforeRankingStart)
						? $pointsRankings[0] ?? null
						: $generalRankings[0] ?? null,
					'points_rankings' => $pointsRankings,
					'general_rankings' => $generalRankings,
					'wealth_rankings' => $factionRankingRepository->getRankingsByField($ranking, 'wealthPosition'),
					'territorial_rankings' => $factionRankingRepository->getRankingsByField($ranking, 'territorialPosition'),
					'points_to_win' => $this->getParameter('points_to_win'),
				] : [
					'has_ranking' => false,
				],
		);
	}
}
