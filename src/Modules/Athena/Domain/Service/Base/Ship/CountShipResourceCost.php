<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Model\Player;

readonly class CountShipResourceCost
{
	public function __construct(
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	public function __invoke(int $identifier, int $quantity, Player|null $manufacturer = null): int
	{
		$manufacturer ??= $this->currentPlayerRegistry->get();
		// débit des ressources au joueur
		$resourceCost = ($this->getShipCategoriesConfiguration)($identifier, 'resourcePrice') * $quantity;
		// TODO Refactor the way faction bonuses are retrieved and applied using BonusApplierInterface
		if (in_array($identifier, [ShipCategory::Cruiser, ShipCategory::HeavyCruiser])) {
			if (in_array(ColorResource::PRICEBIGSHIPBONUS, ($this->getFactionsConfiguration)($manufacturer->faction, 'bonus'))) {
				$resourceCost -= PercentageApplier::toInt($resourceCost, ColorResource::BONUS_EMPIRE_CRUISER);
			}
		}

		return $resourceCost;
	}
}
