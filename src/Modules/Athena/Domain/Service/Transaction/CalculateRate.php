<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Transaction;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Model\Transaction;

readonly class CalculateRate
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	public function __invoke(Transaction $transaction): float
	{
		return match ($transaction->type) {
			Transaction::TYP_RESOURCE => $this->calculateResourceRate($transaction),
			Transaction::TYP_SHIP => $this->calculateShipRate($transaction),
			Transaction::TYP_COMMANDER => $this->calculateCommanderRate($transaction),
			default => throw new \InvalidArgumentException(sprintf('Unknown transaction type %d', $transaction->type)),
		};
	}

	private function calculateResourceRate(Transaction $transaction): float
	{
		return $transaction->price / $transaction->quantity;
	}

	private function calculateShipRate(Transaction $transaction): float
	{
		if (null === ($shipCategory = ShipCategory::tryFrom($transaction->identifier))) {
			throw new \InvalidArgumentException(sprintf('Invalid ship category %d', $transaction->identifier));
		}

		$resourceQuantity = ($this->getShipCategoriesConfiguration)($shipCategory, 'resourcePrice') * $transaction->quantity;

		return $transaction->price / $resourceQuantity;
	}

	private function calculateCommanderRate(Transaction $transaction): float
	{
		return $transaction->price / $transaction->quantity;
	}
}
