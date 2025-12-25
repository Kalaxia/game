<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Repository;

use App\Modules\Economy\Domain\Entity\PlanetActivity;
use App\Modules\Economy\Domain\Repository\PlanetActivityRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<PlanetActivity>
 */
class PlanetActivityRepository extends DoctrineRepository implements PlanetActivityRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, PlanetActivity::class);
	}
}
