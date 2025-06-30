<?php

declare(strict_types=1);

namespace App\Modules\Athena\Application\Handler\CommercialRoute;

use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Zeus\Model\Player;

readonly class GetCommercialRoutePrice
{
	public function __construct(
		private GetFactionsConfiguration $getFactionsConfiguration,
	) {
	}

	public function __invoke(int $distance, ?Player $player = null): int
	{
		$price = $distance * CommercialRoute::COEF_PRICE;

		if (null !== $player) {
			$price = $this->applyBonuses($price, $player);
		}

		return intval(round($price));
	}

	private function applyBonuses(int $price, Player $player): float
	{
		// TODO Refactor faction economic bonuses to merge with player bonus management
		$factionBonus = ($this->getFactionsConfiguration)($player->faction, 'bonus');

		if (in_array(ColorResource::COMMERCIALROUTEPRICEBONUS, $factionBonus)) {
			// bonus if the player is from Negore
			$price -= $price * ColorResource::BONUS_NEGORA_ROUTE / 100;
		}

		return $price;
	}
}
