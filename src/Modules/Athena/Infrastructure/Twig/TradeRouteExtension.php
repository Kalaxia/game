<?php

namespace App\Modules\Athena\Infrastructure\Twig;

use App\Modules\Athena\Application\Handler\CommercialRoute\GetCommercialRouteIncome;
use App\Modules\Athena\Application\Handler\CommercialRoute\GetCommercialRoutePrice;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TradeRouteExtension extends AbstractExtension
{
	public function __construct(
		private readonly CurrentPlayerRegistry    $currentPlayerRegistry,
		private readonly GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		private readonly GetCommercialRoutePrice  $getCommercialRoutePrice,
		private readonly GetCommercialRouteIncome $getCommercialRouteIncome,
	) {
	}

	public function getFunctions(): array
	{
		return [
			new TwigFunction('get_route_price', fn (float $distance) => ($this->getCommercialRoutePrice)($distance, $this->currentPlayerRegistry->get())),
			new TwigFunction('get_route_income', fn (OrbitalBase $from, OrbitalBase $to) => ($this->getCommercialRouteIncome)($from, $to, $this->currentPlayerRegistry->get())),
			new TwigFunction('get_route_distance', fn (CommercialRoute $commercialRoute) => ($this->getDistanceBetweenPlaces)(
				$commercialRoute->originBase->place,
				$commercialRoute->destinationBase->place,
			)),
		];
	}
}
