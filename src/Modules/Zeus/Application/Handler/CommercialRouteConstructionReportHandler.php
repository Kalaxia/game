<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Athena\Application\Handler\CommercialRoute\GetCommercialRoutePrice;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Galaxy\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Zeus\Model\PlayerFinancialReport;

readonly class CommercialRouteConstructionReportHandler
{
	public function __construct(
		private GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		private GetCommercialRoutePrice $getCommercialRoutePrice,
		private CommercialRouteRepositoryInterface $commercialRouteRepository,
	) {
	}

	public function __invoke(PlayerFinancialReport $playerFinancialReport, ?PlayerFinancialReport $lastPlayerFinancialReport): void
	{
		$commercialRoutes = $this->commercialRouteRepository->getPlayerConstructedRoutesSince(
			$playerFinancialReport->player,
			$lastPlayerFinancialReport->createdAt ?? $playerFinancialReport->player->dInscription,
		);

		foreach ($commercialRoutes as $commercialRoute) {
			$playerFinancialReport->commercialRoutesConstructions += ($this->getCommercialRoutePrice)(
				($this->getDistanceBetweenPlaces)(
					$commercialRoute->originBase,
					$commercialRoute->destinationBase,
				),
				$playerFinancialReport->player,
			);
		}
	}
}
