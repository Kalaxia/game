<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Transaction;

use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Model\Transaction;

readonly class GetMaxPriceRelativeToRate
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	public function __invoke(int $transactionType, int $quantity, ?int $identifier = null): int
	{
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				$minRate = Transaction::MAX_RATE_RESOURCE;
				break;
			case Transaction::TYP_SHIP:
				$minRate = Transaction::MAX_RATE_SHIP;
				$quantity = ($this->getShipCategoriesConfiguration)($identifier, 'resourcePrice') * $quantity;
				break;
			case Transaction::TYP_COMMANDER:
				$minRate = Transaction::MAX_RATE_COMMANDER;
				break;
			default:
				throw new \LogicException('Unknown transaction type');
		}

		$price = $quantity * $minRate;

		return intval(round($price));
	}
}
