<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ColorRepository extends DoctrineRepository implements ColorRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Color::class);
	}

	public function get(Uuid $id): ?Color
	{
		return $this->find($id);
	}

	public function getOneByIdentifier(int $identifier): ?Color
	{
		return $this->findOneBy([
			'identifier' => $identifier,
		]);
	}

	/**
	 * @return list<Color>
	 */
	public function getInGameFactions(): array
	{
		return $this->findBy(['isInGame' => 1, 'alive' => 1]);
	}

	/**
	 * @return list<Color>
	 */
	public function getOpenFactions(): array
	{
		return $this->findBy(['isClosed' => 0]);
	}

	/**
	 * @return list<Color>
	 */
	public function getAllByActivePlayersNumber(): array
	{
		$qb = $this->createQueryBuilder('f');

		$qb
			->select('f AS faction, COUNT(p.id) AS active_players')
			->leftJoin(Player::class, 'p', Join::WITH, 'IDENTITY(p.faction) = f.id AND p.status = :active_status')
			->groupBy('f.id')
			->orderBy('active_players', 'ASC')
			->setParameter('active_status', Player::ACTIVE);

		return $qb->getQuery()->getResult();
	}

	public function getByRegimesAndMandateStates($regimes, $mandateStates): array
	{
		return $this->findBy([
			'regime' => $regimes,
			'mandateState' => $mandateStates,
		]);
	}
}
