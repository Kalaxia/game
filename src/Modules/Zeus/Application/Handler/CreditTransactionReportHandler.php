<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Model\PlayerFinancialReport;

readonly class CreditTransactionReportHandler
{
	public function __construct(
		private CreditTransactionRepositoryInterface $creditTransactionRepository,
	) {
	}

	public function __invoke(PlayerFinancialReport $playerFinancialReport, ?PlayerFinancialReport $lastPlayerFinancialReport): void
	{
		$creditTransactions = $this->creditTransactionRepository->matchAllByPlayerSince(
			$playerFinancialReport->player,
			$lastPlayerFinancialReport->createdAt ?? $playerFinancialReport->player->dInscription,
		);

		foreach ($creditTransactions as $creditTransaction) {
			if ($playerFinancialReport->player->id === $creditTransaction->playerSender?->id) {
				if (null !== $creditTransaction->factionReceiver) {
					$playerFinancialReport->sentFactionsCreditTransactions += $creditTransaction->amount;
				} else {
					$playerFinancialReport->sentPlayersCreditTransactions += $creditTransaction->amount;
				}
			} else {
				if (null !== $creditTransaction->factionSender) {
					$playerFinancialReport->receivedFactionsCreditTransactions += $creditTransaction->amount;
				} else {
					$playerFinancialReport->receivedPlayersCreditTransactions += $creditTransaction->amount;
				}
			}
		}
	}
}
