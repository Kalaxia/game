<?php

declare(strict_types=1);

namespace App\Modules\Ares\Repository;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Repository\SquadronRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends DoctrineRepository<Squadron>
 */
class SquadronRepository extends DoctrineRepository implements SquadronRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Squadron::class);
	}

	public function getFactionFleetStats(Color $faction): array
	{
		$qb = $this->createQueryBuilder('s');

		$rows = $qb
			->select('s.shipCategory AS category', 'SUM(s.ships) AS ships')
			->join('s.commander', 'c')
			->join('c.player', 'p')
			->where('p.faction = :faction')
			->andWhere($qb->expr()->in('c.statement', [Commander::AFFECTED, Commander::MOVING, Commander::IN_BATTLE]))
			->groupBy('s.shipCategory')
			->setParameter('faction', $faction->id, UuidType::NAME)
			->getQuery()
			->getArrayResult();

		$stats = [];
		foreach (ShipCategory::cases() as $category) {
			$stats['nbs'.$category->value] = 0;
		}
		foreach ($rows as $row) {
			$category = $row['category'] instanceof ShipCategory ? $row['category']->value : (int) $row['category'];
			$stats['nbs'.$category] = (int) $row['ships'];
		}

		return $stats;
	}

	public function getCommanderSquadrons(Commander $commander): array
	{
		return $this->findBy(
			['commander' => $commander],
			orderBy: ['q' => 'ASC', 'r' => 'ASC'],
		);
	}
}
