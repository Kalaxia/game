<?php

declare(strict_types=1);

namespace App\Modules\Ares\Repository;

use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Model\Report;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ReportRepository extends DoctrineRepository implements ReportRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Report::class);
	}

	public function get(Uuid $id): Report|null
	{
		return $this->find($id);
	}

	public function getByAttackerAndPlace(Player $attacker, Planet $place, \DateTimeImmutable $dFight): array
	{
		return $this->findBy([
			'attacker' => $attacker,
			'place' => $place,
			'fight' => $dFight,
		]);
	}

	public function getAttackReportsByPlaces(Player $attacker, array $places): array
	{
		$qb = $this->createQueryBuilder('r');

		$qb->where('r.attacker = :attacker')
			->andWhere($qb->expr()->in('r.place', ':places'))
			->setParameter('attacker', $attacker)
			->setParameter('places', array_map(
				fn(Uuid $uuid) => $uuid->toBinary(),
				$places
			), ArrayParameterType::BINARY);

		return $qb->getQuery()->getResult();
	}

	public function removePlayerReports(Player $player): void
	{
		$qb = $this->createQueryBuilder('r');

		$qb->delete()
			->where('r.attacker = :player')
			->orWhere('r.defender = :player')
			->setParameter('player', $player);

		$qb->getQuery()->execute();
	}
}
