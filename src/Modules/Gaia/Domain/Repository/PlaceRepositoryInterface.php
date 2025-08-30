<?php

namespace App\Modules\Gaia\Domain\Repository;

use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Place>
 */
interface PlaceRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Place|null;

	/**
	 * @param list<Uuid> $ids
	 * @return list<Place>
	 */
	public function getByIds(array $ids): array;

	/**
	 * @return list<Place>
	 */
	public function getSystemPlaces(System $system): array;

	/**
	 * @return Collection<Place>
	 */
	public function getAll(): Collection;

	/**
	 * @return list<Uuid>
	 */
	public function findPlacesIdsForANewBase(Sector $sector): array;

	/**
	 * @return list<Place>
	 */
	public function search(string $search): array;

	public function npcQuickfix(): void;
}
