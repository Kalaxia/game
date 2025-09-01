<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

class CommercialRouteRepository extends DoctrineRepository implements CommercialRouteRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, CommercialRoute::class);
	}

	public function get($id): CommercialRoute|null
	{
		return $this->find($id);
	}

	public function searchCandidates(Player $player, Planet $planet, array $factions, int $minDistance, int $maxDistance): array
	{
		$factionIdentifiers = sprintf("(%s)", implode(',', $factions));

		return array_map(
			fn (array $data) => array_merge($data, ['placeId' => Uuid::fromBinary($data['placeId'])]),
			$this->getEntityManager()->getConnection()->fetchAllAssociative(
				<<<SQL
					SELECT
					faction.identifier AS factionIdentifier,
					player.avatar AS playerAvatar,
					player.name AS playerName,
					ob.name AS baseName,
					ob.place_id AS placeId,
					(FLOOR(SQRT(POW(:system_x - s.xPosition, 2) + POW(:system_y - s.yPosition, 2)))) AS distance
					FROM orbitalBase AS ob
					INNER JOIN player ON ob.player_id = player.id
					INNER JOIN color faction ON faction.id = player.faction_id
					INNER JOIN place AS p ON ob.place_id = p.id
					INNER JOIN system AS s ON p.system_id = s.id
					INNER JOIN sector AS se ON s.sector_id = se.id
					WHERE player.id != :player_id
					AND ob.levelSpatioport > 0
					AND (FLOOR(SQRT(POW(:system_x - s.xPosition, 2) + POW(:system_y - s.yPosition, 2)))) >= :min_distance
					AND (FLOOR(SQRT(POW(:system_x - s.xPosition, 2) + POW(:system_y - s.yPosition, 2)))) <= :max_distance
					AND faction.identifier IN $factionIdentifiers
					ORDER BY distance DESC
					LIMIT 40
				SQL,
				[
					'system_x' => $planet->place->system->xPosition,
					'system_y' => $planet->place->system->yPosition,
					'player_id' => $player->id,
					'min_distance' => $minDistance,
					'max_distance' => $maxDistance,
				],
			),
		);
	}

	/**
	 * @throws Exception
	 */
	public function getAllPlayerRoutes(Player $player): array
	{
		$qb = $this->createQueryBuilder('cr');

		$qb
			->select()
			->leftJoin('cr.originBase', 'ob')
			->join('ob.place', 'obp')
			->leftJoin('cr.destinationBase', 'db')
			->join('db.place', 'dbp')
			->where('db.player = :player')
			->orWhere('ob.player = :player')
			->setParameter('player', $player);

		return $qb->getQuery()->getResult();
	}

	/**
	 * @throws Exception
	 */
	public function countCommercialRoutesBetweenFactions(Color $faction, Color $otherFaction): int
	{
		$qb = $this->createQueryBuilder('cr');

		$qb->select('COUNT(cr.id)')
			->join('cr.originBase', 'ob1')
			->join('ob1.player', 'p1')
			->join('cr.destinationBase', 'ob2')
			->join('ob2.player', 'p2')
			->where($qb->expr()->orX(
				$qb->expr()->andX(
					$qb->expr()->eq('p1.faction', ':faction'),
					$qb->expr()->eq('p2.faction', ':other_faction'),
				),
				$qb->expr()->andX(
					$qb->expr()->eq('p1.faction', ':other_faction'),
					$qb->expr()->eq('p2.faction', ':faction'),
				),
			))
			->andWhere('cr.statement = :statement')
			->setParameter('faction', $faction->id, UuidType::NAME)
			->setParameter('other_faction', $otherFaction->id, UuidType::NAME)
			->setParameter('statement', CommercialRoute::ACTIVE)
		;

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getCommercialRouteFactionData(Color $faction): array
	{
		$qb = $this->createQueryBuilder('cr');

		$qb->select(
			'COUNT(cr.id) AS nb',
			'SUM(cr.income) AS income',
		)
			->join('cr.originBase', 'ob1')
			->join('ob1.player', 'p1')
			->join('cr.destinationBase', 'ob2')
			->join('ob2.player', 'p2')
			->where($qb->expr()->orX(
				$qb->expr()->eq('p1.faction', ':faction'),
				$qb->expr()->eq('p2.faction', ':faction'),
			))
			->andWhere('cr.statement = :statement')
			->setParameter('faction', $faction->id, UuidType::NAME)
			->setParameter('statement', CommercialRoute::ACTIVE)
		;

		return $qb->getQuery()->getSingleResult();
	}

	public function getInternalCommercialRouteFactionData(Color $faction): array
	{
		$qb = $this->createQueryBuilder('cr');

		$qb->select(
			'COUNT(cr.id) AS nb',
			'SUM(cr.income) AS income',
		)
			->join('cr.originBase', 'ob1')
			->join('ob1.player', 'p1')
			->join('cr.destinationBase', 'ob2')
			->join('ob2.player', 'p2')
			->where($qb->expr()->andX(
				$qb->expr()->eq('p1.faction', ':faction'),
				$qb->expr()->eq('p2.faction', ':faction'),
			))
			->andWhere('cr.statement = :statement')
			->setParameter('faction', $faction->id, UuidType::NAME)
			->setParameter('statement', CommercialRoute::ACTIVE)
		;

		return $qb->getQuery()->getSingleResult();
	}

	public function getByIdAndPlanet(Uuid $id, Planet $planet): CommercialRoute|null
	{
		return $this->findOneBy([
			'id' => $id,
			'originBase' => $planet,
		]);
	}

	public function getByIdAndDistantPlanet(Uuid $id, Planet $planet): CommercialRoute|null
	{
		return $this->findOneBy([
			'id' => $id,
			'destinationBase' => $planet,
		]);
	}

	/**
	 * @return list<CommercialRoute>
	 */
	public function getByPlanet(Planet $planet): array
	{
		return $this->findBy([
			'originBase' => $planet,
		]);
	}

	/**
	 * @return list<CommercialRoute>
	 */
	public function getByDistantPlanet(Planet $planet): array
	{
		return $this->findBy([
			'destinationBase' => $planet,
		]);
	}

	public function getPlanetRoutes(Planet $planet): array
	{
		$qb = $this->createQueryBuilder('cr');

		return $qb
			->where($this->getBaseEndpointsExpr($qb))
			->setParameter('base', $planet->id, UuidType::NAME)
			->getQuery()
			->getResult();
	}

	public function getExistingRoute(Planet $planet, Planet $distantPlanet): CommercialRoute|null
	{
		$qb = $this->createQueryBuilder('cr');

		return $qb
			->where($this->getBoundBasesStatement($qb))
			->setParameter('base', $planet)
			->setParameter('distant_base', $distantPlanet)
			->getQuery()
			->getOneOrNullResult();
	}

	/**
	 * @throws NonUniqueResultException
	 * @throws NoResultException
	 */
	public function getPlanetIncome(Planet $planet): int
	{
		$qb = $this->createQueryBuilder('cr');

		$qb
			->select('SUM(cr.income) AS total_income')
			->where(
				$qb->expr()->andX(
					$this->getBaseEndpointsExpr($qb),
					$qb->expr()->eq('cr.statement', CommercialRoute::ACTIVE),
				),
			)
			->setParameter('base', $planet->id, UuidType::NAME);

		return intval($qb->getQuery()->getSingleScalarResult() ?? 0);
	}

	/**
	 * @throws NonUniqueResultException
	 * @throws NoResultException
	 */
	public function countPlanetRoutes(Planet $planet, array $statements = []): int
	{
		$qb = $this->createQueryBuilder('cr');

		$qb
			->select('COUNT(cr.id) AS nb_routes')
			->where(
				$qb->expr()->andX(
					$this->getBaseEndpointsExpr($qb),
					([] !== $statements) ? $qb->expr()->in('cr.statement', $statements) : null,
				),
			)
			->setParameter('base', $planet->id, UuidType::NAME);

		return intval($qb->getQuery()->getSingleScalarResult() ?? 0);
	}

	public function freezeRoutes(Color $faction, Color $otherFaction, bool $freeze): void
	{
		$qb = $this->createQueryBuilder('cr');

		$subQuery = $this->getEntityManager()->createQueryBuilder();
		$subQuery
			->select('cr.id')
			->from(CommercialRoute::class, 'cr')
			->leftJoin('cr.originBase', 'ob1')
			->leftJoin('ob1.player', 'pl1')
			->leftJoin('cr.destinationBase', 'ob2')
			->leftJoin('ob2.player', 'pl2')
			->where($subQuery->expr()->andX(
				$subQuery->expr()->orX(
					$subQuery->expr()->andX(
						$subQuery->expr()->eq('pl1.faction', ':faction'),
						$subQuery->expr()->eq('pl2.faction', ':other_faction'),
					),
					$subQuery->expr()->andX(
						$subQuery->expr()->eq('pl1.faction', ':other_faction'),
						$subQuery->expr()->eq('pl2.faction', ':faction'),
					),
				),
				$subQuery->expr()->eq(
					'cr.statement',
					(true === $freeze) ? CommercialRoute::ACTIVE : CommercialRoute::STANDBY,
				),
			))
			->setParameter('faction', $faction->id, UuidType::NAME)
			->setParameter('other_faction', $otherFaction->id, UuidType::NAME)
		;

		$qb
			->update(CommercialRoute::class, 'commercial_route')
			->set('commercial_route.statement', (true === $freeze) ? CommercialRoute::STANDBY : CommercialRoute::ACTIVE)
			->where($qb->expr()->in('commercial_route.id', $subQuery->getDQL()))
			->getQuery()->execute([
				'faction' => $faction,
				'other_faction' => $otherFaction,
			]);
	}

	public function getPlayerConstructedRoutesSince(Player $player, \DateTimeImmutable $since): array
	{

		$qb = $this->createQueryBuilder('cr');

		$qb
			->select()
			->leftJoin('cr.originBase', 'ob')
			->join('ob.place', 'obp')
			->leftJoin('cr.destinationBase', 'db')
			->join('db.place', 'dbp')
			->where($qb->expr()->orX(
				$qb->expr()->andX(
					'db.player = :player',
					$qb->expr()->gte('cr.acceptedAt', ':since'),
				),
				$qb->expr()->andX(
					'ob.player = :player',
					$qb->expr()->gte('cr.proposedAt', ':since'),
				),
			))
			->setParameter('player', $player)
			->setParameter('since', $since);

		return $qb->getQuery()->getResult();
	}

	private function getBoundBasesStatement(QueryBuilder $qb): Expr\Orx
	{
		return $qb->expr()->orX(
			$qb->expr()->andX(
				$qb->expr()->eq('cr.originBase', ':base'),
				$qb->expr()->eq('cr.destinationBase', ':distant_base'),
			),
			$qb->expr()->andX(
				$qb->expr()->eq('cr.originBase', ':distant_base'),
				$qb->expr()->eq('cr.destinationBase', ':base'),
			),
		);
	}

	private function getBaseEndpointsExpr(QueryBuilder $qb): Expr\Orx
	{
		return $qb->expr()->orX(
			$qb->expr()->eq('cr.originBase', ':base'),
			$qb->expr()->eq('cr.destinationBase', ':base'),
		);
	}
}
