<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service;

use App\Classes\Library\Game;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\ShipResource;

readonly class CountNeededCommercialShips
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	public function __invoke(int $transactionType, int $quantity = 1, int|string $identifier = 0): int
	{
		return match ($transactionType) {
			// 1000 ressources => 1 commercialShip
			Transaction::TYP_RESOURCE => intval(ceil($quantity / 1000)),
			// 1 PEV => 1 commercialShip
			Transaction::TYP_SHIP => (null !== ($shipCategory = ShipCategory::tryFrom(intval($identifier))) and $quantity > 0)
				? $quantity * ($this->getShipCategoriesConfiguration)($shipCategory, 'pev')
				: throw new \LogicException('Invalid ship or quantity'),
			// 1 commander => 1 commercialShip
			Transaction::TYP_COMMANDER => 1,
			default => throw new \LogicException('Unknown transaction type'),
		};
	}
}
