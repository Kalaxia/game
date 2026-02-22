<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Repository;

use App\Modules\Ares\Model\Commander;
use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

class PlayerRankingRepository extends DoctrineRepository implements PlayerRankingRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, PlayerRanking::class);
	}

	public function getFactionPlayerRankings(Ranking $ranking, Color $faction): array
	{
		$qb = $this->createQueryBuilder('pr');

		$qb
			->join('pr.player', 'p')
			->where('p.faction = :faction')
			->andWhere('pr.ranking = :ranking')
			->setParameter('faction', $faction)
			->setParameter('ranking', $ranking);

		return $qb->getQuery()->getResult();
	}

	public function getPlayerLastRanking(Player $player): ?PlayerRanking
	{
		$qb = $this->createQueryBuilder('pr');

		$qb
			->orderBy('pr.createdAt', 'DESC')
			->where('pr.player = :player')
			->setParameter('player', $player)
			->setMaxResults(1);

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getBestPlayerRanking(): ?PlayerRanking
	{
		return $this->findOneBy([], [
			'generalPosition' => 'ASC',
		]);
	}

	public function getAttackersButcherRanking(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb->select(
			'p.id AS player_id,
				(SUM(r.attacker_pev_at_beginning) - SUM(r.attacker_pev_at_end)) AS lostPEV,
				(SUM(r.defender_pev_at_beginning) - SUM(r.defender_pev_at_end)) AS destroyedPEV,
				(SUM(r.attacker_pev_at_beginning) - SUM(r.attacker_pev_at_end) - SUM(r.defender_pev_at_beginning) - SUM(r.defender_pev_at_end)) AS score'
		)
			->from('report', 'r')
			->join('r', 'player', 'p', 'r.attacker_id = p.id')
			->where('p.statement IN (:statements)')
			->setParameter('statements', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY], Types::SIMPLE_ARRAY)
			->groupBy('p.id')
			->orderBy('p.id');

		return $qb->executeQuery();
	}

	public function getDefendersButcherRanking(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb->select(
			'p.id AS player_id,
				(SUM(r.defender_pev_at_beginning) - SUM(r.defender_pev_at_end)) AS lostPEV,
				(SUM(r.attacker_pev_at_beginning) - SUM(r.attacker_pev_at_end)) AS destroyedPEV,
				(SUM(r.defender_pev_at_beginning) - SUM(r.defender_pev_at_end) - SUM(r.attacker_pev_at_beginning) - SUM(r.attacker_pev_at_end)) AS score'
		)
			->from('report', 'r')
			->join('r', 'player', 'p', 'r.defender_id = p.id')
			->where('p.statement IN (:statements)')
			->setParameter('statements', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY], Types::SIMPLE_ARRAY)
			->groupBy('p.id')
			->orderBy('p.id');

		return $qb->executeQuery();
	}

	public function getRankingsByRange(Ranking $ranking, string $field, int $offset, int $limit): array
	{
		return $this->findBy([
			'ranking' => $ranking,
		], [$field => 'ASC'], $limit, $offset);
	}

	public function getPlayersResources(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb
			->select('p.id AS player, pl.level_refinery AS levelRefinery, pl.coef_resources AS coefResources')
			->from('galaxy__planets', 'pl')
			->join('pl', 'player', 'p', 'p.id = pl.player_id')
			->where('p.statement IN (:statements)')
			->setParameter('statements', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY], Types::SIMPLE_ARRAY);

		return $qb->executeQuery();
	}

	public function getPlayersResourcesData(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb
			->select('p.id AS player, SUM(pl.resources_storage) AS sumResources')
			->from('galaxy__planets', 'pl')
			->join('pl', 'player', 'p', 'p.id = pl.player_id')
			->where('p.statement IN (:statements)')
			->setParameter('statements', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY], Types::SIMPLE_ARRAY)
			->groupBy('pl.id');

		return $qb->executeQuery();
	}

	public function getPlayersGeneralData(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb->select('
				p.id AS player,
				SUM(pl.points) AS points,
				SUM(pl.resources_storage) AS resources,
				pl.ship_storage
			')
			->from('galaxy__planets', 'pl')
			->join('pl', 'player', 'p', 'p.id = pl.player_id')
			->where('p.statement IN (:statements)')
			->setParameter('statements', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY], Types::SIMPLE_ARRAY)
			->groupBy('p.id');

		return $qb->executeQuery();
	}

	public function getPlayersArmiesData(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb->select('
				p.id AS player,
				SUM(sq.ship0) as s0,
				SUM(sq.ship1) as s1,
				SUM(sq.ship2) as s2,
				SUM(sq.ship3) as s3,
				SUM(sq.ship4) as s4,
				SUM(sq.ship5) as s5,
				SUM(sq.ship6) as s6,
				SUM(sq.ship7) as s7,
				SUM(sq.ship8) as s8,
				SUM(sq.ship9) as s9,
				SUM(sq.ship10) as s10,
				SUM(sq.ship11) as s11
			')
			->from('squadron', 'sq')
			->join('sq', 'commander', 'c', 'c.id = sq.commander_id')
			->join('c', 'player', 'p', 'p.id = c.player_id')
			->where('c.statement IN (:statements)')
			->setParameter('statements', [Commander::AFFECTED, Commander::MOVING], Types::SIMPLE_ARRAY)
			->groupBy('p.id');

		return $qb->executeQuery();
	}

	public function getPlayersPlanetData(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb->select(
			'p.id AS player,
				COUNT(pl.id) AS sumPlanets'
		)
			->from('galaxy__planets', 'pl')
			->join('pl', 'player', 'p', 'p.id = pl.player_id')
			->where('p.statement IN (:statements)')
			->setParameter('statements', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY], Types::SIMPLE_ARRAY)
			->groupBy('p.id');

		return $qb->executeQuery();
	}

	public function getPlayersTradeRoutes(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb->select(
			'p.id AS player,
				SUM(c.income) AS income'
		)
			->from('commercialRoute', 'c')
			->join('c', 'galaxy__planets', 'pl', 'pl.id = c.origin_base_id')
			->join('pl', 'player', 'p', 'p.id = pl.player_id')
			->where('p.statement IN (:statements)')
			->setParameter('statements', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY], Types::SIMPLE_ARRAY)
			->groupBy('p.id')
			->orderBy('p.id');

		return $qb->executeQuery();
	}

	public function getPlayersLinkedTradeRoutes(): Result
	{
		$qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

		$qb->select(
			'p.id AS player,
				SUM(c.income) AS income'
		)
			->from('commercialRoute', 'c')
			->join('c', 'galaxy__planets', 'pl', 'pl.id = c.destination_base_id')
			->join('pl', 'player', 'p', 'p.id = pl.player_id')
			->where('p.statement IN (:statements)')
			->setParameter('statements', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY], Types::SIMPLE_ARRAY)
			->groupBy('p.id')
			->orderBy('p.id');

		return $qb->executeQuery();
	}
}
