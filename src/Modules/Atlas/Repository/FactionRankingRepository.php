<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Repository;

use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Atlas\Model\FactionRanking;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

class FactionRankingRepository extends DoctrineRepository implements FactionRankingRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, FactionRanking::class);
	}

	public function getRoutesIncome(Color $faction): array
	{
		$qb = $this->createQueryBuilder('fr');

		$qb->select('COUNT(cr.id) as nb, SUM(cr.income) as income')
			->from(CommercialRoute::class, 'cr')
			->join('cr.originBase', 'ob')
			->join('ob.player', 'obp')
			->join('cr.destinationBase', 'db')
			->join('db.player', 'dbp')
			->where($qb->expr()->orX(
				$qb->expr()->eq('obp.faction', ':faction'),
				$qb->expr()->eq('dbp.faction', ':faction'),
			))
			->andWhere('cr.statement = :statement')
			->setParameter('faction', $faction)
			->setParameter('statement', CommercialRoute::ACTIVE);

		return $qb->getQuery()->getSingleResult();
	}

	public function getLastRanking(Color $faction): ?FactionRanking
	{
		$qb = $this->createQueryBuilder('fr');

		$qb
			->where('fr.faction = :faction')
			->orderBy('fr.createdAt', 'DESC')
			->setParameter('faction', $faction)
			->setMaxResults(1);

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getRankingsByField(Ranking $ranking, string $field): array
	{
		return $this->findBy(['ranking' => $ranking], [$field => 'DESC']);
	}

	public function getFactionRankings(Color $faction): array
	{
		return $this->findBy([
			'faction' => $faction,
		], [
			'createdAt' => 'DESC',
		], 20, 0);
	}
}
