<?php

namespace App\Modules\Zeus\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Player>
 */
class PlayerRepository extends DoctrineRepository implements PlayerRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Player::class);
	}

	public function get(int $id): Player|null
	{
		return $this->find($id);
	}

	public function getByName(string $name): Player|null
	{
		return $this->findOneBy(['name' => $name]);
	}

	public function getByBindKey(string $bindKey): Player|null
	{
		return $this->findOneBy(['bind' => $bindKey]);
	}

	public function getGodSons(Player $player): array
	{
		return $this->findBy(['rGodFather' => $player]);
	}

	public function getByIdsAndStatements(array $ids, array $statements): array
	{
		$qb = $this->createQueryBuilder('p');

		return $qb
			->andWhere($qb->expr()->in('p.id', $ids))
			->andWhere($qb->expr()->in('p.statement', $statements))
			->getQuery()
			->getResult();
	}

	public function getByStatements(array $statements): array
	{
		$qb = $this->createQueryBuilder('p');

		return $qb
			->andWhere($qb->expr()->in('p.statement', $statements))
			->getQuery()
			->getResult();
	}

	public function countActivePlayers(): int
	{
		return $this->createQueryBuilder('p')
			->select('COUNT(p.id)')
			->where('p.statement = :statement')
			->setParameter('statement', Player::ACTIVE)
			->getQuery()
			->getSingleScalarResult();
	}

	public function countAllPlayers(): int
	{
		return $this->createQueryBuilder('p')
			->select('COUNT(p.id)')
			->where('p.statement = :statement')
			->setParameter('statement', [Player::ACTIVE, Player::INACTIVE])
			->getQuery()
			->getSingleScalarResult();
	}

	public function countByFactionAndStatements(Color $faction, array $statements): int
	{
		return $this->createQueryBuilder('p')
			->select('COUNT(p.id)')
			->where('p.statement = :statement')
			->andWhere('p.faction = :faction')
			->setParameter('faction', $faction)
			->setParameter('statement', $statements)
			->getQuery()
			->getSingleScalarResult();
	}

	public function getFactionAccount(Color $faction): Player
	{
		return $this->findOneBy([
			'faction' => $faction,
			'statement' => Player::DEAD,
		], ['id' => 'ASC']);
	}

	public function getBySpecification(SelectorSpecification $specification): array
	{
		$queryBuilder = $this->createQueryBuilder('p');

		$specification->addMatchingCriteria($queryBuilder);

		return $queryBuilder->getQuery()->getResult();
	}

	/**
	 * @return list<Player>
	 */
	public function getFactionPlayersByRanking(Color $faction): array
	{
		return $this->createQueryBuilder('p')
			->where('p.faction = :faction')
			->andWhere('p.statement != :statement')
			->setParameters([
				'faction' => $faction,
				'statement' => Player::DEAD,
			])
			->orderBy('p.factionPoint', 'DESC')
			->getQuery()
			->getResult();
	}

	/**
	 * @return list<Player>
	 */
	public function getFactionPlayersByName(Color $faction): array
	{
		return $this->createQueryBuilder('p')
			->where('p.faction = :faction')
			->andWhere('p.statement = :statement')
			->setParameters([
				'faction' => $faction,
				'statement' => [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY],
			])
			->orderBy('name', 'ASC')
			->getQuery()
			->getResult();
	}

	/**
	 * @return list<Player>
	 */
	public function getLastFactionPlayers(Color $faction): array
	{
		return $this->createQueryBuilder('p')
			->where('p.faction = :faction')
			->andWhere('p.statement != :statement')
			->setParameters([
				'faction' => $faction,
				'statement' => Player::DEAD,
			])
			->orderBy('p.dInscription', 'DESC')
			->setMaxResults(25)
			->getQuery()
			->getResult();
	}

	public function getGovernmentMember(Color $faction, int $status): Player|null
	{
		return $this->createQueryBuilder('p')
			->where('p.faction = :faction')
			->andWhere('p.status = :status')
			->andWhere('p.statement != :statement')
			->setParameters([
				'faction' => $faction,
				'status' => $status,
				'statement' => Player::DEAD,
			])
			->getQuery()
			->getOneOrNullResult();
	}

	public function getFactionLeader(Color $faction): Player
	{
		return $this->getGovernmentMember($faction, Player::CHIEF);
	}

	/**
	 * @return list<Player>
	 */
	public function getActivePlayers(): array
	{
		return $this->findBy([
			'statement' => Player::ACTIVE,
		]);
	}

	/**
	 * @return list<Player>
	 */
	public function search(string $search): array
	{
		return $this->createQueryBuilder('p')
			->where('LOWER(p.name) = LOWER(:search)')
			->setParameter('search', $search)
			->orderBy('p.experience', 'DESC')
			->setMaxResults(20)
			->getQuery()
			->getResult();
	}

	public function updatePlayerCredits(Player $player, int $credits): mixed
	{
		return $this->createQueryBuilder('p')
			->update()
			->set('p.credit', 'p.credit + :credits')
			->where('p.id = :player_id')
			->getQuery()
			->execute([
				'player_id' => $player->id,
				'credits' => $credits,
			]);
	}
}
