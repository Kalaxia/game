<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\Repository\Doctrine;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Planet>
 */
class PlanetRepository extends DoctrineRepository implements PlanetRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Planet::class);
	}

	public function get(Uuid $id): Planet|null
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

	public function getPlayerPlanets(Player $player): array
	{
		return $this->findBy([
			'player' => $player,
		], [
			'createdAt' => 'ASC',
		]);
	}

	public function countPlayerPlanets(Player $player): int
	{
		return $this->count([
			'player' => $player,
		]);
	}

	public function getSectorPlanets(Sector $sector): array
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

	public function getSystemPlanets(System $system): array
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
