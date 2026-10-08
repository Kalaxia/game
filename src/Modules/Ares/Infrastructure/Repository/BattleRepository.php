<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Repository;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Battle>
 */
class BattleRepository extends DoctrineRepository implements BattleRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Battle::class);
	}

	public function get(Uuid $id): ?Battle
	{
		return $this->find($id);
	}
}
