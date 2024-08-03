<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Ship;

use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Model\Player;

readonly class GetResourceCost
{
	public function __construct(
		private CurrentPlayerRegistry $currentPlayerRegistry,
	) {

	}

	public function __invoke(int $identifier, int $quantity, Player|null $manufacturer = null): int
	{
		$manufacturer ??= $this->currentPlayerRegistry->get();
		// débit des ressources au joueur
		$resourceCost = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;
		// TODO Refactor the way faction bonuses are retrieved and applied using BonusApplierInterface
		if (in_array($identifier, [ShipResource::CERBERE, ShipResource::PHENIX])) {
			if (in_array(ColorResource::PRICEBIGSHIPBONUS, ColorResource::getInfo($manufacturer->faction->identifier, 'bonus'))) {
				$resourceCost -= PercentageApplier::toInt($resourceCost, ColorResource::BONUS_EMPIRE_CRUISER);
			}
		}

		return $resourceCost;
	}
}
