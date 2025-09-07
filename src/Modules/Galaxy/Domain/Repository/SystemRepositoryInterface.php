<?php

namespace App\Modules\Galaxy\Domain\Repository;

use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<System>
 */
interface SystemRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): System|null;

	/**
	 * @return list<System>
	 */
	public function getAll(): array;

	/**
	 * @return list<System>
	 */
	public function getSectorSystems(Sector $sector): array;
}
