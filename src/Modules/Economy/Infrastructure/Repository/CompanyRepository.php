<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Economy\Domain\Entity\Company;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Repository\CompanyRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Company>
 */
class CompanyRepository extends DoctrineRepository implements CompanyRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Company::class);
	}

	public function get(Uuid $id): Company|null
	{
		return $this->find($id);
	}

	public function searchCandidateCompaniesForNewActivity(
		Activity $activity,
		?Color   $faction = null,
		int      $limit = 12,
	): array {
		$queryBuilder = $this->createQueryBuilder('c');
		$queryBuilder->where('c.activity = :activity');
		$queryBuilder->andWhere('c.credits >= :cost');
		$queryBuilder->setParameter('activity', $activity);
		$queryBuilder->setParameter('cost', $activity->getCost());
		$queryBuilder->setMaxResults($limit);

		if (null !== $faction) {
			$queryBuilder->andWhere('c.faction = :faction');
			$queryBuilder->setParameter('faction', $faction);
		}

		return $queryBuilder->getQuery()->getResult();
	}
}
