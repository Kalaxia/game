<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Domain\Entity\News;
use App\Modules\Hermes\Domain\Repository\NewsRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends DoctrineRepository<News>
 */
class NewsRepository extends DoctrineRepository implements NewsRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, News::class);
	}

	public function countUnreadNewsForPlayer(Player $player): int
	{
		$qb = $this->createQueryBuilder('n');

		$qb
			->select('COUNT(n.id)')
			->where('n.recipient = :recipient')
			->setParameter('recipient', $player->faction->id, UuidType::NAME);

		if (null !== $player->lastReadNewsAt) {
			$qb->andWhere('n.createdAt > :since')
				->setParameter('since', $player->lastReadNewsAt);
		}

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getAll(Color $faction, int $limit = 30, int $offset = 0): array
	{
		$qb = $this->createQueryBuilder('n');

		$qb
			->setMaxResults($limit)
			->setFirstResult($offset)
			->where('n.recipient = :recipient')
			->setParameter('recipient', $faction->id, UuidType::NAME)
			->orderBy('n.createdAt', 'DESC');

		return $qb->getQuery()->getResult();
	}
}
