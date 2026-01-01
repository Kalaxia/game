<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Zeus\Model\PlayerFinancialReport;

readonly class TechnologyInvestmentReportHandler
{
	public function __construct(
		private TechnologyQueueRepositoryInterface $technologyQueueRepository,
		private TechnologyHelper $technologyHelper,
	) {
	}

	public function __invoke(
		PlayerFinancialReport $playerFinancialReport,
		PlayerFinancialReport|null $lastPlayerFinancialReport,
	): void {
		$queues = $this->technologyQueueRepository->matchPlayerQueuesSince(
			$playerFinancialReport->player,
			$lastPlayerFinancialReport->createdAt ?? $playerFinancialReport->player->dInscription,
		);

		foreach ($queues as $queue) {
			$playerFinancialReport->technologiesInvestments += intval(
				$this->technologyHelper->getInfo($queue->technology, 'credit', $queue->targetLevel)
			);
		}
	}
}
