<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class BuildingQueueRepository extends DoctrineRepository implements BuildingQueueRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, BuildingQueue::class);
	}

	public function get(Uuid $id): BuildingQueue|null
	{
		return $this->find($id);
	}

	public function getPlanetQueues(Planet $planet): array
	{
		return $this->findBy([
			'base' => $planet,
		], [
			'startedAt' => 'ASC',
		]);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}
}
