<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Repository;

use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

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

	public function getAll(): Collection
	{
		return $this->matching(
			Criteria::create()
				->where(Criteria::expr()->eq('typeOfPlace', Place::TERRESTRIAL))
				->orderBy(['id' => 'ASC'])
		);
	}

	public function findPlacesIdsForANewBase(Sector $sector): array
	{
		$qb = $this->createQueryBuilder('p');

		$qb
			->select('p.id')
			->join('p.system', 'sys')
			->where('IDENTITY(sys.sector) = :sector_id')
			->andWhere('p.player IS NULL')
			->andWhere('p.typeOfPlace = :type_of_place')
			->setParameter('type_of_place', Place::TERRESTRIAL)
			->setParameter('sector_id', $sector->id->toBinary())
			->orderBy('p.population', 'ASC')
			->setMaxResults(30);

		return array_map(
			fn (string $bytes) => Uuid::fromBinary($bytes),
			$qb->getQuery()->getSingleColumnResult(),
		);
	}

	public function search(string $search): array
	{
		$qb = $this->createQueryBuilder('p');

		$qb
			->join('p.player', 'pl')
			->join('p.base', 'ob')
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
