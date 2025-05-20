<?php

namespace App\Modules\Gaia\Domain\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Sector>
 */
interface SectorRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Sector|null;

	public function getOneByIdentifier(int $identifier): Sector|null;

	/**
	 * @return list<Sector>
	 */
	public function getFactionSectors(Color $faction): array;

	public function countFactionSectors(Color $faction): int;

	/**
	 * @return list<Sector>
	 */
	public function getAll(): array;
}
