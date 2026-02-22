<?php

declare(strict_types=1);

namespace App\Modules\Ares\Repository;

use App\Modules\Ares\Domain\Repository\LiveReportRepositoryInterface;
use App\Modules\Ares\Model\Report;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class LiveReportRepository extends DoctrineRepository implements LiveReportRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Report::class);
	}

	public function get(Uuid $id): ?Report
	{
		return $this->find($id);
	}

	public function getPlayerReports(Player $player): array
	{
		$qb = $this->createQueryBuilder('r');

		$qb->where(
			$qb->expr()->orX(
				$qb->expr()->andX(
					$qb->expr()->eq('r.attacker', ':attacker'),
					$qb->expr()->eq('r.attackerStatement', Report::STANDARD),
				),
				$qb->expr()->andX(
					$qb->expr()->eq('r.defender', ':defender'),
					$qb->expr()->eq('r.defenderStatement', Report::STANDARD),
				),
			),
		)
		->setParameter('attacker', $player)
		->setParameter('defender', $player);

		return $qb->getQuery()->getResult();
	}

	public function getAttackReportsByPlaces(Player $player, array $places): array
	{
		$qb = $this->createQueryBuilder('r');

		$qb
			->where('r.attacker = :player')
			->andWhere($qb->expr()->in('r.place', ':places'))
			->setParameter('player', $player)
			->setParameter('places', $places)
			->orderBy('r.foughtAt', 'DESC')
			->setMaxResults(30);

		return $qb->getQuery()->getResult();
	}

	public function getAttackReportsByMode(Player $player, bool $hasRebels, bool $isArchived): array
	{
		$qb = $this->createQueryBuilder('r');

		$qb
			->where('r.attacker = :player')
			->andWhere('r.attackerStatement = :statement')
			->setParameter('player', $player)
			->setParameter('statement', $isArchived ? Report::ARCHIVED : Report::STANDARD)
			->orderBy('r.foughtAt', 'DESC')
			->setMaxResults(50);

		if (!$hasRebels) {
			$qb->andWhere('r.defender IS NOT NULL');
		}

		return $qb->getQuery()->getResult();
	}

	public function getDefenseReportsByMode(Player $player, bool $hasRebels, bool $isArchived): array
	{
		$qb = $this->createQueryBuilder('r');

		$qb
			->where('r.defender = :player')
			->andWhere('r.defenderStatement = :statement')
			->setParameter('player', $player)
			->setParameter('statement', $isArchived ? Report::ARCHIVED : Report::STANDARD)
			->orderBy('r.foughtAt', 'DESC')
			->setMaxResults(50);

		return $qb->getQuery()->getResult();
	}

	public function getFactionAttackReports(Color $faction): array
	{
		$qb = $this->createQueryBuilder('r');

		$qb->join('r.attacker', 'a')
			->where('a.faction = :faction')
			->andWhere('r.defender IS NOT NULL')
			->setParameter('faction', $faction)
			->orderBy('r.foughtAt', 'DESC')
			->setMaxResults(30)
		;

		return $qb->getQuery()->getResult();
	}

	public function getFactionDefenseReports(Color $faction): array
	{
		$qb = $this->createQueryBuilder('r');

		$qb->join('r.defender', 'd')
			->where('d.faction = :faction')
			->setParameter('faction', $faction)
			->orderBy('r.foughtAt', 'DESC')
			->setMaxResults(30)
		;

		return $qb->getQuery()->getResult();
	}
}
