<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

class OrbitalBaseRepository extends DoctrineRepository implements OrbitalBaseRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, OrbitalBase::class);
	}

	public function get(Uuid $id): OrbitalBase|null
	{
		return $this->find($id);
	}

	public function getBySpecification(SelectorSpecification $specification): array
	{
		$qb = $this->createQueryBuilder('ob');

		$qb->join('ob.player', 'p');

		$specification->addMatchingCriteria($qb);

		return $qb->getQuery()->getResult();
	}

	public function getPlayerBases(Player $player): array
	{
		return $this->findBy([
			'player' => $player,
		], [
			'createdAt' => 'ASC',
		]);
	}

	public function getPlayerBasesCount(Player $player): int
	{
		return $this->count([
			'player' => $player,
		]);
	}

	public function getSectorBases(Sector $sector): array
	{
		$qb = $this->createQueryBuilder('ob');

		return $qb
			->join('ob.place', 'place')
			->join('place.system', 'system')
			->where('system.sector = :sector')
			->setParameter('sector', $sector->id, UuidType::NAME)
			->getQuery()
			->getResult();
	}

	public function getSystemBases(System $system): array
	{
		$qb = $this->createQueryBuilder('ob');

		return $qb
			->join('ob.place', 'place')
			->where('place.system = :system')
			->setParameter('system', $system->id, UuidType::NAME)
			->getQuery()
			->getResult();
	}
}
