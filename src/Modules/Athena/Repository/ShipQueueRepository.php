<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ShipQueueRepository extends DoctrineRepository implements ShipQueueRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ShipQueue::class);
	}

	public function get(Uuid $id): ShipQueue|null
	{
		return $this->find($id);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getBaseQueues(Planet $base): array
	{
		return $this->findBy([
			'base' => $base,
		]);
	}

	public function getByBaseAndDockType(Planet $base, int $dockType): array
	{
		return $this->findBy([
			'base' => $base,
			'dockType' => $dockType,
		], [
			'startedAt' => 'ASC',
		]);
	}
}
