<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Zeus\Model\PlayerFinancialReport;

readonly class PlayerTransactionReportHandler
{
	public function __construct(
		private TransactionRepositoryInterface $transactionRepository,
	) {
	}

	public function __invoke(PlayerFinancialReport $playerFinancialReport, ?PlayerFinancialReport $lastFinancialReport): void
	{
		$transactions = $this->transactionRepository->matchPlayerCompletedTransactionsSince(
			$playerFinancialReport->player,
			$lastFinancialReport->createdAt ?? $playerFinancialReport->player->dInscription,
		);

		foreach ($transactions as $transaction) {
			$buyerPrice = $transaction->getTotalPrice();
			$sellerPrice = $transaction->price;

			// Current player is the seller
			if ($transaction->player->id === $playerFinancialReport->player->id) {
				switch ($transaction->type) {
					case Transaction::TYP_RESOURCE:
						$playerFinancialReport->resourcesSales += $sellerPrice;
						break;
					case Transaction::TYP_SHIP:
						$playerFinancialReport->shipsSales += $sellerPrice;
						break;
					case Transaction::TYP_COMMANDER:
						$playerFinancialReport->commandersSales += $sellerPrice;
						break;
				}

				continue;
			}
			switch ($transaction->type) {
				case Transaction::TYP_RESOURCE:
					$playerFinancialReport->resourcesPurchases += $buyerPrice;
					break;
				case Transaction::TYP_SHIP:
					$playerFinancialReport->shipsPurchases += $buyerPrice;
					break;
				case Transaction::TYP_COMMANDER:
					$playerFinancialReport->commandersPurchases += $buyerPrice;
					break;
			}
		}
	}
}
