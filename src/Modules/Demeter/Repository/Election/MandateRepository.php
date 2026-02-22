<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Mandate;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends DoctrineRepository<Mandate>
 */
class MandateRepository extends DoctrineRepository implements MandateRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Mandate::class);
	}

	public function getCurrentMandate(Color $faction): ?Mandate
	{
		$qb = $this->createQueryBuilder('m');

		$qb->where('m.faction = :faction')
			->andWhere('m.startedAt <= :now')
			->andWhere('m.expiredAt > :now')
			->setParameter('faction', $faction->id, UuidType::NAME)
			->setParameter('now', new \DateTimeImmutable());

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getLastMandate(Color $faction): ?Mandate
	{
		$qb = $this->createQueryBuilder('m');

		$qb->where('m.faction = :faction')
			->orderBy('m.expiredAt', 'DESC')
			->setParameter('faction', $faction->id, UuidType::NAME);

		return $qb->getQuery()->getOneOrNullResult();
	}
}
