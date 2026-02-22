<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Athena\Domain\Repository\RecyclingLogRepositoryInterface;
use App\Modules\Zeus\Model\PlayerFinancialReport;

readonly class RecycledCreditsReportHandler
{
	public function __construct(
		private RecyclingLogRepositoryInterface $recyclingLogRepository,
	) {
	}

	public function __invoke(PlayerFinancialReport $playerFinancialReport, ?PlayerFinancialReport $lastPlayerFinancialReport): void
	{
		$playerFinancialReport->recycledCredits += $this->recyclingLogRepository->getPlayerRecycledCreditsSince(
			$playerFinancialReport->player,
			$lastPlayerFinancialReport->createdAt ?? $playerFinancialReport->player->dInscription,
		);
	}
}
