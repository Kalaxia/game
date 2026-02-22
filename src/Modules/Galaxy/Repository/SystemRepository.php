<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Repository;

use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class SystemRepository extends DoctrineRepository implements SystemRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, System::class);
	}

	public function get(Uuid $id): ?System
	{
		return $this->find($id);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getSectorSystems(Sector $sector): array
	{
		return $this->findBy([
			'sector' => $sector,
		]);
	}
}
