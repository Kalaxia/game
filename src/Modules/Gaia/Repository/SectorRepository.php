<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Repository;

use App\Classes\Entity\AbstractRepository;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class SectorRepository extends DoctrineRepository implements SectorRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Sector::class);
	}

	public function get(Uuid $id): Sector|null
	{
		return $this->find($id);
	}

	public function getOneByIdentifier(int $identifier): Sector|null
	{
		return $this->findOneBy(['identifier' => $identifier]);
	}

	public function getFactionSectors(Color $faction): array
	{
		return $this->findBy([
			'faction' => $faction,
		]);
	}

	public function countFactionSectors(Color $faction): int
	{
		return $this->count([
			'faction' => $faction,
		]);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}
}
