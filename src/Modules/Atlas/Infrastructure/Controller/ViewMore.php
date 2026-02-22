<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ViewMore extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		RankingRepositoryInterface $rankingRepository,
		PlayerRankingRepositoryInterface $playerRankingRepository,
	): Response {
		$direction = $request->query->get('direction') ?? throw new BadRequestHttpException('Missing direction');
		$current = $request->query->get('current') ?? throw new BadRequestHttpException('Missing current');
		$type = $request->query->get('type') ?? throw new BadRequestHttpException('Missing type');

		if (!in_array($direction, ['next', 'prev'])) {
			throw new BadRequestHttpException('Invalid direction');
		}

		if (!in_array($type, ['general', 'resources', 'xp', 'fight', 'armies', 'butcher', 'trader'])) {
			throw new BadRequestHttpException('Invalid type');
		}

		$ranking = $rankingRepository->getLastRanking() ?? throw new ConflictHttpException('No ranking available yet');

		// var
		$fty = ('xp' == $type)
			? 'experience'
			: $type;

		$bot = ('next' == $direction)
			? (($current - PlayerRanking::PAGE > 1) ? $current - PlayerRanking::PAGE : 1)
			: $current + 1;

		$size = (1 == $bot)
			? $current - 1
			: PlayerRanking::PAGE;

		$playerRankings = $playerRankingRepository->getRankingsByRange($ranking, $fty.'Position', $bot - 1, $size);

		return $this->render('components/rankings/player/rankings_part.html.twig', [
			'current_player' => $currentPlayer,
			'player_rankings' => $playerRankings,
			'direction' => $direction,
			'type' => $type,
			'bot' => $bot,
			'fty' => $fty,
		]);
	}
}
