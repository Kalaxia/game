<?php

declare(strict_types=1);

namespace App\Modules\Ares\Repository;

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

		$qb
			->select(
				'SUM(s.ship0) AS nbs0',
				'SUM(s.ship1) AS nbs1',
				'SUM(s.ship2) AS nbs2',
				'SUM(s.ship3) AS nbs3',
				'SUM(s.ship4) AS nbs4',
				'SUM(s.ship5) AS nbs5',
				'SUM(s.ship6) AS nbs6',
				'SUM(s.ship7) AS nbs7',
				'SUM(s.ship8) AS nbs8',
				'SUM(s.ship9) AS nbs9',
				'SUM(s.ship10) AS nbs10',
				'SUM(s.ship11) AS nbs11'
			)
			->join('s.commander', 'c')
			->join('c.player', 'p')
			->where('p.faction = :faction')
			->andWhere($qb->expr()->in('c.statement', [Commander::AFFECTED, Commander::MOVING]))
			->setParameter('faction', $faction->id, UuidType::NAME);

		return $qb->getQuery()->getSingleResult();
	}

	public function getCommanderSquadrons(Commander $commander): array
	{
		return $this->findBy(
			['commander' => $commander],
			orderBy: ['position' => 'ASC'],
		);
	}
}
