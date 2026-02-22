<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Repository;

use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;

/**
 * @extends EntityRepositoryInterface<PlayerFinancialReport>
 */
interface PlayerFinancialReportRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<PlayerFinancialReport>
	 */
	public function getPlayerLastReports(Player $player, int $limit = 20, int $offset = 0): array;

	public function getPlayerLastReport(Player $player): ?PlayerFinancialReport;

	public function cleanPlayerFinancialReports(int $timeout): int;
}
