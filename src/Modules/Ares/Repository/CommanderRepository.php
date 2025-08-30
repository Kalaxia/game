<?php

declare(strict_types=1);

namespace App\Modules\Ares\Repository;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

class CommanderRepository extends DoctrineRepository implements CommanderRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Commander::class);
	}

	public function get(Uuid $id): Commander|null
	{
		return $this->find($id);
	}

	public function getBySpecification(SelectorSpecification $specification): array
	{
		$qb = $this->createQueryBuilder('c');

		$qb->join('c.player', 'p');

		$specification->addMatchingCriteria($qb);

		return $qb->getQuery()->getResult();
	}

	public function getCommandersByIds(array $ids = []): array
	{
		$qb = $this->createQueryBuilder('c');

		return $qb
			->andWhere($qb->expr()->in('c.id', $ids))
			->getQuery()
			->getResult();
	}

	public function getMovingCommanders(): array
	{
		return $this->findBy(['statement' => Commander::MOVING]);
	}

	public function getPlayerCommanders(Player $player, array $statements = [], array $orderBy = []): array
	{
		$qb = $this->createQueryBuilder('c');

		foreach ($orderBy as $field => $order) {
			$qb->addOrderBy($field, $order);
		}

		return $qb
			->andWhere($qb->expr()->in('c.statement', ':statements'))
			->andWhere('c.player = :player')
			->setParameter('statements', $statements)
			->setParameter('player', $player)
			->getQuery()
			->getResult();
	}

	public function getBaseCommanders(OrbitalBase $orbitalBase, array $statements = [], array $orderBy = []): array
	{
		return $this->findBy([
			'base' => $orbitalBase,
			'statement' => $statements,
		], $orderBy);
	}

	public function getCommandersByLine(OrbitalBase $orbitalBase, int $line): array
	{
		return $this->findBy([
			'base' => $orbitalBase,
			'line' => $line,
		]);
	}

	public function getIncomingAttacks(Player $player): array
	{
		$qb = $this->createQueryBuilder('c');

		return $qb
			->join('c.destinationPlace', 'dp')
			->join('dp.player', 'player')
			->andWhere('dp.player = :player')
			->andWhere('c.player != :player')
			->andWhere($qb->expr()->eq('c.statement', Commander::MOVING))
			->andWhere($qb->expr()->in('c.travelType', [Commander::COLO, Commander::LOOT]))
			->setParameter('player', $player)
			->getQuery()
			->getResult();
	}

	public function getOutcomingAttacks(Player $player): array
	{
		return $this->findBy([
			'player' => $player,
			'statement' => Commander::MOVING,
		]);
	}

	public function getIncomingCommanders(Place $place): array
	{
		return $this->findBy([
			'destinationPlace' => $place,
			'statement' => Commander::MOVING,
		], ['dArrival' => 'ASC']);
	}

	public function countCommandersByLine(OrbitalBase $orbitalBase, int $line): int
	{
		$qb = $this->createQueryBuilder('c');

		return $qb
			->select('COUNT(c)')
			->andWhere('c.base = :orbital_base')
			->andWhere('c.line = :line')
			->andWhere($qb->expr()->in('c.statement', [Commander::AFFECTED, Commander::MOVING]))
			->setParameter('orbital_base', $orbitalBase->id, UuidType::NAME)
			->setParameter('line', $line)
			->getQuery()
			->getSingleScalarResult();
	}

	public function getFactionCommanderStats(Color $faction): array
	{
		$qb = $this->createQueryBuilder('c');

		$qb
			->select(
				'COUNT(c.id) AS nb',
				'AVG(c.level) AS avgLevel',
			)
			->join('c.player', 'p')
			->where('p.faction = :faction')
			->andWhere($qb->expr()->in('c.statement', [Commander::AFFECTED, Commander::MOVING]))
			->setParameter('faction', $faction->id, UuidType::NAME);

		return $qb->getQuery()->getSingleResult();
	}
}
