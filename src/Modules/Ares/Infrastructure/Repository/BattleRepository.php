<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Repository;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Battle>
 */
class BattleRepository extends DoctrineRepository implements BattleRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Battle::class);
	}

	public function get(Uuid $id): ?Battle
	{
		return $this->find($id);
	}

	public function getRunningBattleAt(Planet $planet): ?Battle
	{
		return $this->findOneBy(['place' => $planet, 'endedAt' => null]);
	}

	public function getPlayerBattles(Player $player, int $limit = 50): array
	{
		return $this->createQueryBuilder('b')
			->join('b.players', 'p')
			->where('p = :player')
			->setParameter('player', $player)
			->orderBy('b.startedAt', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->getResult();
	}

	public function getPlayerBattlesAt(Player $player, array $placeIds): array
	{
		if ([] === $placeIds) {
			return [];
		}

		return $this->createQueryBuilder('b')
			->join('b.players', 'p')
			->where('p = :player')
			->andWhere('b.place IN (:places)')
			->setParameter('player', $player)
			->setParameter('places', array_map(fn (Uuid $id) => $id->toBinary(), $placeIds), ArrayParameterType::BINARY)
			->orderBy('b.startedAt', 'DESC')
			->getQuery()
			->getResult();
	}

	public function getFactionBattles(Color $faction, int $limit = 20): array
	{
		return $this->createQueryBuilder('b')
			->join('b.players', 'p')
			->where('p.faction = :faction')
			->setParameter('faction', $faction->id, UuidType::NAME)
			->orderBy('b.startedAt', 'DESC')
			->distinct()
			->setMaxResults($limit)
			->getQuery()
			->getResult();
	}
}
