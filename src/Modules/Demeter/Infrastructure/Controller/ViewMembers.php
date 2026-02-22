<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewMembers extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		DurationHandler $durationHandler,
		PlayerRepositoryInterface $playerRepository,
	): Response {
		$faction = $currentPlayer->faction;

		$onlinePlayersCount = 0;
		$offlinePlayersCount = 0;

		$victoriesCount = 0;
		$defeatsCount = 0;
		$pointsCount = 0;
		$playersCount = 0;

		$playersByType = [
			'Gouvernement' => [],
			'Sénat' => [],
			'Peuple' => [],
		];
		$factionPlayers = $playerRepository->getFactionPlayersByRanking($faction);

		foreach ($factionPlayers as $factionPlayer) {
			if ($durationHandler->getDiff(new \DateTimeImmutable(), $factionPlayer->dLastActivity) < 600) {
				++$onlinePlayersCount;
			} else {
				++$offlinePlayersCount;
			}

			++$playersCount;
			$victoriesCount += $factionPlayer->victory;
			$defeatsCount += $factionPlayer->defeat;
			$pointsCount += $factionPlayer->experience;

			$type = match ($factionPlayer->status) {
				Player::STANDARD => 'Peuple',
				Player::PARLIAMENT => 'Sénat',
				default => 'Gouvernement',
			};
			$playersByType[$type][] = $factionPlayer;
		}

		return $this->render('pages/demeter/faction/members.html.twig', [
			'faction' => $faction,
			'players_by_type' => $playersByType,
			'players_count' => $playersCount,
			'last_faction_players' => $playerRepository->getLastFactionPlayers($faction),
			'online_players_count' => $onlinePlayersCount,
			'offline_players_count' => $offlinePlayersCount,
			'victories_average' => $victoriesCount,
			'defeats_average' => $defeatsCount,
			'points_average' => $pointsCount,
		]);
	}
}
