<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Manager\PlanetManager;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsGovernmentMember;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ViewEmbassy extends AbstractController
{
	public function __invoke(
		Request                   $request,
		Player                    $currentPlayer,
		ColorManager              $colorManager,
		ColorRepositoryInterface  $colorRepository,
		PlayerRepositoryInterface $playerRepository,
		PlanetManager             $planetManager,
		PlanetRepositoryInterface $planetRepository,
		PlayerManager             $playerManager,
		SectorRepositoryInterface $sectorRepository,
	): Response {
		$data = [];

		if (null !== ($playerId = $request->query->get('player'))) {
			if (null === ($player = $playerRepository->get($playerId)) || !$player->isInGame()) {
				throw new NotFoundHttpException('Player not found');
			}

			$data = [
				'player' => $player,
				'player_planets' => $planetRepository->getPlayerPlanets($player),
				'is_current_player' => $playerId === $currentPlayer->id,
			];
		}

		if (null !== ($factionId = $request->query->get('faction')) || null === $playerId) {
			$factionId ??= $currentPlayer->faction->identifier;

			if (null !== ($faction = $colorRepository->getOneByIdentifier($factionId)) && $faction->isInGame) {
				$data = [
					'faction' => $faction,
					'government_members' => $playerRepository->getBySpecification(new IsGovernmentMember($faction)),
					'diplomacy_statements' => [
						Color::ENEMY => 'En guerre',
						Color::ALLY => 'Allié',
						Color::PEACE => 'Pacte de non-agression',
						Color::NEUTRAL => 'Neutre',
					],
					'sectors_count' => $sectorRepository->countFactionSectors($faction),
					'active_players_count' => $playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]),
				];
			} else {
				throw new NotFoundHttpException('Faction not found');
			}
		}

		return $this->render('pages/demeter/embassy.html.twig', array_merge($data, [
			'factions' => $colorRepository->getInGameFactions(),
		]));
	}
}
