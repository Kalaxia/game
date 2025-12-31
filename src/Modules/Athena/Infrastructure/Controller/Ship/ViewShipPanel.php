<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewShipPanel extends AbstractController
{
	#[Route(
		path: '/ships/{shipNumber}/panel',
		name: 'load_ship_panel',
		methods: [Request::METHOD_POST],
	)]
	public function __invoke(
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		int $shipNumber,
	): Response {
		$playerBonuses = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses;

		switch ($getShipCategoriesConfiguration($shipNumber, 'class')) {
			case 0:
				$bonusSPE = $playerBonuses->get(PlayerBonusId::FIGHTER_SPEED);
				$bonusATT = $playerBonuses->get(PlayerBonusId::FIGHTER_ATTACK);
				$bonusDEF = $playerBonuses->get(PlayerBonusId::FIGHTER_DEFENSE);
				break;
			case 1:
				$bonusSPE = $playerBonuses->get(PlayerBonusId::CORVETTE_SPEED);
				$bonusATT = $playerBonuses->get(PlayerBonusId::CORVETTE_ATTACK);
				$bonusDEF = $playerBonuses->get(PlayerBonusId::CORVETTE_DEFENSE);
				break;
			case 2:
				$bonusSPE = $playerBonuses->get(PlayerBonusId::FRIGATE_SPEED);
				$bonusATT = $playerBonuses->get(PlayerBonusId::FRIGATE_ATTACK);
				$bonusDEF = $playerBonuses->get(PlayerBonusId::FRIGATE_DEFENSE);
				break;
			case 3:
				$bonusSPE = $playerBonuses->get(PlayerBonusId::DESTROYER_SPEED);
				$bonusATT = $playerBonuses->get(PlayerBonusId::DESTROYER_ATTACK);
				$bonusDEF = $playerBonuses->get(PlayerBonusId::DESTROYER_DEFENSE);
				break;
			default:
				$bonusSPE = 0;
				$bonusATT = 0;
				$bonusDEF = 0;
				break;
		}

		$attacks = $getShipCategoriesConfiguration($shipNumber, 'attack');

		return $this->render('blocks/athena/ship_panel.html.twig', [
			'ship_number' => $shipNumber,
			'bonus_att' => $bonusATT,
			'bonus_def' => $bonusDEF,
			'bonus_spe' => $bonusSPE,
			'attacks' => $attacks,
			'damage' => array_unique($attacks),
			'nb_shots' => array_count_values($attacks),
			'life' => ($shipNumber > 5) ? 1600 : 135,
			'defense' => ($shipNumber > 5) ? 200 : 25,
			'speed_a' => ($shipNumber > 5) ? 25 : 100,
			'speed_b' => ($shipNumber > 5) ? 250 : 150,
			'attack' => ($shipNumber > 5) ? 700 : 90,
		]);
	}
}
