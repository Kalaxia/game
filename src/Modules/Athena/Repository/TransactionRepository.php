<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class TransactionRepository extends DoctrineRepository implements TransactionRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Transaction::class);
	}

	public function get(Uuid $id): Transaction|null
	{
		return $this->find($id);
	}

	public function getLastCompletedTransaction(int $type): Transaction|null
	{
		return $this->findOneBy(
			[
				'type' => $type,
				'statement' => Transaction::ST_COMPLETED,
			],
			[
				'validatedAt' => 'DESC',
			],
		);
	}

	public function getProposedTransactions(int $type): array
	{
		$qb = $this->createQueryBuilder('t');

		$qb
			->join('t.player', 'p')
			->where($qb->expr()->andX(
				$qb->expr()->neq('p.statement', Player::DEAD),
				$qb->expr()->eq('t.statement', Transaction::ST_PROPOSED),
				$qb->expr()->eq('t.type', $type),
			))
			->orderBy('t.publishedAt', 'DESC')
			->setMaxResults(20)
		;

		return $qb->getQuery()->getResult();
	}

	public function getPlayerPropositions(Player $player, int $type): array
	{
		return $this->findBy([
			'player' => $player,
			'type' => $type,
			'statement' => Transaction::ST_PROPOSED,
		]);
	}

	public function getPlanetPropositions(Planet $planet): array
	{
		return $this->findBy([
			'base' => $planet,
			'statement' => Transaction::ST_PROPOSED,
		]);
	}

	public function getExchangeRate(int $type): float
	{
		$qb = $this->createQueryBuilder('t');

		$qb->select('t.currentRate')
			->where('t.type = :type')
			->andWhere('t.statement = :statement')
			->orderBy('t.validatedAt', 'DESC')
			->setMaxResults(1)
			->setParameter('type', $type)
			->setParameter('statement', Transaction::ST_COMPLETED);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function matchPlayerCompletedTransactionsSince(Player $player, \DateTimeImmutable $completedAt): Collection
	{
		return $this->matching(new Criteria(
			Criteria::expr()->andX(
				Criteria::expr()->orX(
					Criteria::expr()->eq('player', $player),
					Criteria::expr()->eq('buyer', $player),
				),
				Criteria::expr()->eq('statement', Transaction::ST_COMPLETED),
				Criteria::expr()->gte('validatedAt', $completedAt),
			)
		));
	}
}
