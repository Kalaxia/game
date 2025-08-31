<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class RecyclingMissionRepository extends DoctrineRepository implements RecyclingMissionRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, RecyclingMission::class);
	}

	public function get(Uuid $id): RecyclingMission|null
	{
		return $this->find($id);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getBaseMissions(Planet $base): array
	{
		return $this->findBy(['base' => $base]);
	}

	public function getPlanetActiveMissions(Planet $planet): array
	{
		return $this->findBy([
			'base' => $planet,
			'statement' => [RecyclingMission::ST_ACTIVE, RecyclingMission::ST_BEING_DELETED],
		]);
	}

	public function removePlanetMissions(Planet $planet): void
	{
		$qb = $this->createQueryBuilder('rm');

		$qb
			->delete()
			->where('rm.base = :base')
			->setParameter('base', $planet);

		$qb->getQuery()->getResult();
	}
}
