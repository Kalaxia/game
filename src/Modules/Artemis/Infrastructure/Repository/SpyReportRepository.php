<?php

declare(strict_types=1);

namespace App\Modules\Artemis\Infrastructure\Repository;

use App\Modules\Artemis\Domain\Repository\SpyReportRepositoryInterface;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class SpyReportRepository extends DoctrineRepository implements SpyReportRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, SpyReport::class);
	}

	public function get(Uuid $id): ?SpyReport
	{
		return $this->find($id);
	}

	public function getSystemReports(Player $player, array $places): array
	{
		$qb = $this->createQueryBuilder('sp');

		$qb
			->where('sp.player = :player')
			->andWhere($qb->expr()->in('sp.place', ':places'))
			->orderBy('sp.createdAt', 'DESC')
			->setMaxResults(30)
			->setFirstResult(0)
			->setParameter('player', $player)
			->setParameter('places', array_map(
				fn (Uuid $uuid) => $uuid->toBinary(),
				$places
			), ArrayParameterType::BINARY);

		return $qb->getQuery()->getResult();
	}

	public function getPlayerReports(Player $player): array
	{
		return $this->findBy([
			'player' => $player,
		], [
			'createdAt' => 'DESC',
		], 40, 0);
	}

	public function deletePlayerReports(Player $player): int
	{
		$qb = $this->createQueryBuilder('s');

		$qb->delete()
			->where('s.player = :player')
			->setParameter('player', $player);

		return $qb->getQuery()->getResult();
	}
}
