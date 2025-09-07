<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\Repository\Doctrine;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Enum\PlaceType;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
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

	public function getAll(): Collection
	{
		return $this->matching(
			Criteria::create()
				->orderBy(['id' => 'ASC'])
		);
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
		$qb = $this->createQueryBuilder('p');

		return $qb
			->join('p.system', 'system')
			->where('system.sector = :sector')
			->setParameter('sector', $sector->id, UuidType::NAME)
			->getQuery()
			->getResult();
	}

	public function getSystemPlanets(System $system): array
	{
		$qb = $this->createQueryBuilder('p');

		return $qb
			->where('p.system = :system')
			->setParameter('system', $system->id, UuidType::NAME)
			->getQuery()
			->getResult();
	}

	public function getCandidatePlanetsForNewPlayers(Sector $sector): array
	{
		$qb = $this->createQueryBuilder('p');

		$qb
			->select('p.id')
			->join('p.system', 'sys')
			->where('IDENTITY(sys.sector) = :sector_id')
			->andWhere('p.player IS NULL')
			->setParameter('sector_id', $sector->id->toBinary())
			->orderBy('p.population', 'ASC')
			->setMaxResults(30);

		return array_map(
			fn (string $bytes) => Uuid::fromBinary($bytes),
			$qb->getQuery()->getSingleColumnResult(),
		);
	}
}
