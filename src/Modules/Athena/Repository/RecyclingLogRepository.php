<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\RecyclingLogRepositoryInterface;
use App\Modules\Athena\Model\RecyclingLog;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

class RecyclingLogRepository extends DoctrineRepository implements RecyclingLogRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, RecyclingLog::class);
	}

	public function getBaseActiveMissionsLogs(Planet $base): array
	{
		$qb = $this->createQueryBuilder('rl');

		$qb
			->join('rl.mission', 'rm')
			->where('rm.base = :base')
			->setParameter('base', $base->id, UuidType::NAME)
			->orderBy('rl.createdAt', 'DESC');

		return $qb->getQuery()->getResult();
	}

	public function getPlayerRecycledCreditsSince(Player $player, \DateTimeImmutable $since): int
	{
		$qb = $this->createQueryBuilder('rl');

		$qb
			->select('SUM(rl.credits)')
			->join('rl.mission', 'rm')
			->join('rm.base', 'ob')
			->where('ob.player = :player')
			->andWhere($qb->expr()->gte('rl.createdAt', ':since'))
			->setParameter('player', $player->id)
			->setParameter('since', $since);

		return intval($qb->getQuery()->getSingleScalarResult());
	}
}
