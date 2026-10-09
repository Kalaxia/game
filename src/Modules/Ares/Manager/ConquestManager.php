<?php

declare(strict_types=1);

namespace App\Modules\Ares\Manager;

use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Prices of colonisation and conquest. The missions themselves are carried out by AccomplishMission.
 */
readonly class ConquestManager
{
	public function __construct(
		#[Autowire('%ares.coeff.colonization_cost%')]
		private int $colonizationCost,
		#[Autowire('%ares.coeff.conquest_cost%')]
		private int $conquestCost,
	) {
	}

	public function getColonizationCost(Player $player, int $totalBases): int
	{
		return $this->processAttackCost($player, $this->colonizationCost, $totalBases);
	}

	public function getConquestCost(Player $player, int $totalBases): int
	{
		return $this->processAttackCost($player, $this->conquestCost, $totalBases);
	}

	private function processAttackCost(Player $player, int $cost, int $totalBases): int
	{
		$price = $cost * $totalBases;

		if (ColorResource::ADRANITE == $player->faction->identifier) {
			// bonus if the player is from Cardan
			$price -= round($price * ColorResource::BONUS_CARDAN_COLO / 100);
		}

		return intval($price);
	}
}
