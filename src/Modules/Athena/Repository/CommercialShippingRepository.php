<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

class CommercialShippingRepository extends DoctrineRepository implements CommercialShippingRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, CommercialShipping::class);
	}

	public function get(Uuid $id): ?CommercialShipping
	{
		return $this->find($id);
	}

	public function getByTransaction(Transaction $transaction): ?CommercialShipping
	{
		return $this->findOneBy([
			'transaction' => $transaction,
		]);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getMoving(): array
	{
		return $this->findBy([
			'statement' => [CommercialShipping::ST_GOING, CommercialShipping::ST_MOVING_BACK],
		]);
	}

	public function getByPlanet(Planet $planet): array
	{
		$qb = $this->createQueryBuilder('cs');

		return $qb
			->innerJoin('cs.transaction', 't')
			->where(
				$qb->expr()->orX(
					$qb->expr()->eq('cs.originBase', ':base'),
					$qb->expr()->eq('cs.destinationBase', ':base'),
				),
			)
			->addOrderBy('t.publishedAt', 'DESC')
			->setParameter('base', $planet->id, UuidType::NAME)
			->getQuery()
			->getResult();
	}
}
