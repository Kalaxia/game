<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Repository;

use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

class RankingRepository extends DoctrineRepository implements RankingRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Ranking::class);
	}

	public function getLastRanking(): ?Ranking
	{
		return $this->findOneBy([], ['createdAt' => 'DESC']);
	}
}
