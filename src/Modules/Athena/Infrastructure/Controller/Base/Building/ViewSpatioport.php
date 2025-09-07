<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewSpatioport extends AbstractController
{
	private const int MIN_DISTANCE = 75;
	private const int MAX_DISTANCE = 125;

	public function __invoke(
        Request                            $request,
        Player                             $currentPlayer,
        CurrentPlayerBonusRegistry         $currentPlayerBonusRegistry,
        Planet                             $currentBase,
        CommercialRouteManager             $commercialRouteManager,
        CommercialRouteRepositoryInterface $commercialRouteRepository,
        ColorManager                       $colorManager,
        ColorRepositoryInterface           $colorRepository,
	): Response {
		if ($currentBase->levelSpatioport === 0) {
			return $this->redirectToRoute('base_overview');
		}

		$mode = $request->query->get('mode', 'list');

		$inGameFactions = $colorRepository->getInGameFactions();

		return $this->render('pages/athena/spatioport.html.twig', [
			'routes' => $commercialRouteRepository->getPlanetRoutes($currentBase),
			'routes_data' => $commercialRouteManager->getBaseCommercialData($currentBase),
			'player_commercial_income_bonus' => $currentPlayerBonusRegistry
				->getPlayerBonus()->bonuses->get(PlayerBonusId::COMMERCIAL_INCOME),
			'negora_commercial_bonus' => ColorResource::BONUS_NEGORA_ROUTE,
			'is_player_from_negora' => ColorResource::FALKIRR === $currentPlayer->faction->identifier,
			'in_game_factions' => $inGameFactions,
			'mode' => $mode,
			'search_results' => ('search' === $mode && 'POST' === $request->getMethod())
				? $commercialRouteRepository->searchCandidates(
					$currentPlayer,
					$currentBase,
					array_reduce($inGameFactions, function (array $carry, Color $faction) use ($request) {
						if ($request->request->has('faction-'.$faction->identifier)) {
							$carry[] = $faction->identifier;
						}

						return $carry;
					}, []),
					abs(intval($request->request->get('min-dist', self::MIN_DISTANCE))),
					abs(intval($request->request->get('max-dist', self::MAX_DISTANCE))),
				) : null,
		]);
	}
}
