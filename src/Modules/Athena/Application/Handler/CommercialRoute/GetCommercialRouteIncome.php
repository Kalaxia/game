<?php

declare(strict_types=1);

namespace App\Modules\Athena\Application\Handler\CommercialRoute;

use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GetCommercialRouteIncome
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		private GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		#[Autowire('%athena.trade.route.sector_bonus%')]
		private float $sectorBonus,
		#[Autowire('%athena.trade.route.color_bonus%')]
		private float $factionBonus,
	) {
	}

	public function __invoke(Planet $from, Planet $to, ?Player $player = null): int
	{
		$bonusA = (!$from->system->sector->id->equals($to->system->sector->id)) ? $this->sectorBonus : 1;
		$bonusB = (!$from->player->faction->id->equals($to->player->faction->id)) ? $this->factionBonus : 1;

		$distance = ($this->getDistanceBetweenPlaces)($from, $to);

		$income = CommercialRoute::COEF_INCOME_2 * sqrt(min($distance, 100) * CommercialRoute::COEF_INCOME_1);

		if (null !== $player) {
			$income += intval($this->bonusApplier->apply($income, PlayerBonusId::COMMERCIAL_INCOME));
		}

		return intval(round($income * $bonusA * $bonusB));
	}
}
