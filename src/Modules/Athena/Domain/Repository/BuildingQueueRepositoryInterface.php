<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

interface BuildingQueueRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?BuildingQueue;

	/**
	 * @return list<BuildingQueue>
	 */
	public function getPlanetQueues(Planet $planet): array;

	/**
	 * @return list<BuildingQueue>
	 */
	public function getAll(): array;
}
