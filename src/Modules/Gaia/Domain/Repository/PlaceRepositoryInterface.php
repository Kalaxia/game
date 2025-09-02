<?php

namespace App\Modules\Gaia\Domain\Repository;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Planet>
 */
interface PlaceRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Planet|null;

	/**
	 * @param list<Uuid> $ids
	 * @return list<Planet>
	 */
	public function getByIds(array $ids): array;

	/**
	 * @return list<Planet>
	 */
	public function getSystemPlaces(System $system): array;

	/**
	 * @return list<Planet>
	 */
	public function search(string $search): array;

	public function npcQuickfix(): void;
}
