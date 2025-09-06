<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Repository;

use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Place>
 */
class PlaceRepository extends DoctrineRepository implements PlaceRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Place::class);
	}

	public function get(Uuid $id): Place|null
	{
		return $this->find($id);
	}

	public function getByIds(array $ids): array
	{
		return $this->findBy([
			'id' => $ids,
		]);
	}

	public function getSystemPlaces(System $system): array
	{
		return $this->findBy(
			['system' => $system],
			['position' => 'ASC'],
		);
	}

	public function search(string $search): array
	{
		$qb = $this->createQueryBuilder('p');

		$qb
			->join('p.base', 'ob')
			->join('ob.player', 'pl')
			->where($qb->expr()->andX(
				$qb->expr()->orX(
					$qb->expr()->in('pl.statement', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY])
				),
				$qb->expr()->orX(
					$qb->expr()->like('LOWER(pl.name)', 'LOWER(:search)'),
					$qb->expr()->like('LOWER(ob.name)', 'LOWER(:search)'),
				),
			))
			->orderBy('pl.id', 'DESC')
			->setMaxResults(20)
			->setParameter('search', "%$search%");

		return $qb->getQuery()->getResult();
	}

	public function npcQuickfix(): void
	{
		$qb = $this->createQueryBuilder('p');

		$qb
			->update()
			->set('p.danger', 'p.maxDanger')
			->where('p.danger > p.maxDanger');

		$qb->getQuery()->execute();
	}
}
