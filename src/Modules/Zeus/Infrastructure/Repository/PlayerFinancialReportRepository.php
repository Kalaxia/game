<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Repository;

use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Domain\Repository\PlayerFinancialReportRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use Doctrine\Persistence\ManagerRegistry;

class PlayerFinancialReportRepository extends DoctrineRepository implements PlayerFinancialReportRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, PlayerFinancialReport::class);
	}

	public function getPlayerLastReports(Player $player, int $limit = 20, int $offset = 0): array
	{
		return $this->findBy(
			[
				'player' => $player,
			],
			[
				'createdAt' => 'DESC',
			],
			$limit,
			$offset,
		);
	}

	public function getPlayerLastReport(Player $player): ?PlayerFinancialReport
	{
		return $this->findOneBy(
			[
				'player' => $player,
			],
			[
				'createdAt' => 'DESC',
			],
		);
	}

	public function cleanPlayerFinancialReports(int $timeout): int
	{
		$qb = $this->createQueryBuilder('pfr');

		$qb
			->delete()
			->where(
				$qb->expr()->gt('DATE_DIFF(CURRENT_DATE(), pfr.createdAt)', ':timeout')
			)
			->setParameter('timeout', $timeout);

		return $qb->getQuery()->getResult();
	}
}
