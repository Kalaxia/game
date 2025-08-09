<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Transaction;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Model\Transaction;

/**
 * Calculates the new rate when a transaction is accepted
 */
readonly class CalculateNewRate
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		private TransactionRepositoryInterface $transactionRepository,
	) {
	}

	public function __invoke(Transaction $transaction): float
	{
		$currentRate = $this->transactionRepository->getExchangeRate($transaction->type);
		
		return match ($transaction->type) {
			Transaction::TYP_RESOURCE => $this->calculateNewResourceRate($transaction, $currentRate),
			Transaction::TYP_SHIP => $this->calculateNewShipRate($transaction, $currentRate),
			Transaction::TYP_COMMANDER => $this->calculateNewCommanderRate($transaction, $currentRate),
			default => throw new \InvalidArgumentException(sprintf('Unknown transaction type %d', $transaction->type)),
		};
	}

	private function calculateNewResourceRate(Transaction $transaction, float $currentRate): float
	{
		// 1 resource = x credit
		$thisRate = $transaction->price / $transaction->quantity;
		// dilution of 1%
		$newRate = (($transaction->quantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $transaction->quantity);

		return max($newRate, Transaction::MIN_RATE_RESOURCE);
	}

	private function calculateNewShipRate(Transaction $transaction, float $currentRate): float
	{
		// 1 resource = x credit
		if (null === ($shipCategory = ShipCategory::tryFrom($transaction->identifier))) {
			throw new \InvalidArgumentException(sprintf('Invalid ship category %d', $transaction->identifier));
		}
		$resourceQuantity = ($this->getShipCategoriesConfiguration)($shipCategory, 'resourcePrice') * $transaction->quantity;
		$thisRate = $transaction->price / $resourceQuantity;
		// dilution of 1%
		$newRate = (($resourceQuantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $resourceQuantity);

		return max($newRate, Transaction::MIN_RATE_SHIP);
	}

	private function calculateNewCommanderRate(Transaction $transaction, float $currentRate): float
	{
		$thisRate = $transaction->price / $transaction->quantity;
		// dilution of 1%
		$newRate = (($transaction->quantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $transaction->quantity);

		return max($newRate, Transaction::MIN_RATE_COMMANDER);
	}
}
