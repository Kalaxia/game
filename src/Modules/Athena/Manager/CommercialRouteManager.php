<?php

declare(strict_types=1);

namespace App\Modules\Athena\Manager;

use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;

readonly class CommercialRouteManager
{
	public function __construct(
		private PlanetHelper $planetHelper,
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private CommercialRouteRepositoryInterface $commercialRouteRepository,
	) {
	}

	/**
	 * @return array{
	 *	waiting_for_me: int,
	 *  waiting_for_other: int,
	 *  operational: int,
	 *  stand_by: int,
	 *  total: int,
	 *  total_income: int,
	 *  max: int
	 * }
	 **/
	public function getBaseCommercialData(Planet $planet): array
	{
		$currentPlayer = $this->currentPlayerRegistry->get();
		$routes = $this->commercialRouteRepository->getPlanetRoutes($planet);
		// if (0 === count($routes)) {
		//	return [];
		// }

		$nCRWaitingForOther = 0;
		$nCRWaitingForMe = 0;
		$nCROperational = 0;
		$nCRInStandBy = 0;
		$totalIncome = 0;

		foreach ($routes as $route) {
			if ($route->isProposed() and $route->originBase->player->id == $currentPlayer->id) {
				++$nCRWaitingForOther;
			} elseif ($route->isProposed() and $route->originBase->player->id != $currentPlayer->id) {
				++$nCRWaitingForMe;
			} elseif ($route->isActive()) {
				$totalIncome += $route->income;
				++$nCROperational;
			} elseif ($route->isInStandBy()) {
				++$nCRInStandBy;
			}
		}

		return [
			'waiting_for_me' => $nCRWaitingForMe,
			'waiting_for_other' => $nCRWaitingForOther,
			'operational' => $nCROperational,
			'stand_by' => $nCRInStandBy,
			'total' => $nCROperational + $nCRInStandBy + $nCRWaitingForOther,
			'total_income' => $totalIncome,
			'max' => $this->planetHelper->getBuildingInfo(
				PlanetResource::SPATIOPORT,
				'level',
				$planet->levelSpatioport,
				'nbRoutesMax'
			),
		];
	}

	public function removeBaseRoutes(Planet $planet): void
	{
		$routes = $this->commercialRouteRepository->getPlanetRoutes($planet);

		foreach ($routes as $route) {
			$this->commercialRouteRepository->remove($route);
			// @TODO notifications
		}
	}

	public function toggleRoutesFreeze(Color $faction, Color $otherFaction): void
	{
		$freeze = Color::ENEMY === $faction->relations[$otherFaction->identifier]
			|| Color::ENEMY === $otherFaction->relations[$faction->identifier];

		$this->commercialRouteRepository->freezeRoutes($faction, $otherFaction, $freeze);
	}
}
