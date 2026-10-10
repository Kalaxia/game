<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Repository;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleTurn;
use App\Modules\Ares\Domain\Repository\BattleTurnRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<BattleTurn>
 */
class BattleTurnRepository extends DoctrineRepository implements BattleTurnRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, BattleTurn::class);
	}

	public function getBattleTurns(Battle $battle): array
	{
		return $this->findBy(['battle' => $battle], ['turn' => 'ASC']);
	}
}
