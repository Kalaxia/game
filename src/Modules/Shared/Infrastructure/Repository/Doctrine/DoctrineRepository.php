<?php

namespace App\Modules\Shared\Infrastructure\Repository\Doctrine;

use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template T of object
 *
 * @extends ServiceEntityRepository<T>
 *
 * @implements EntityRepositoryInterface<T>
 */
abstract class DoctrineRepository extends ServiceEntityRepository implements EntityRepositoryInterface
{
	/**
	 * @param T $entity
	 */
	public function save(object $entity): void
	{
		$this->getEntityManager()->persist($entity);
		$this->getEntityManager()->flush();
	}

	/**
	 * @param T $entity
	 */
	public function remove(object $entity): void
	{
		$this->getEntityManager()->remove($entity);
		$this->getEntityManager()->flush();
	}

	/**
	 * @param T $entity
	 */
	public function detach(object $entity): void
	{
		$this->getEntityManager()->detach($entity);
	}

	/**
	 * @param T $entity
	 */
	public function refresh(object $entity): void
	{
		$this->getEntityManager()->refresh($entity);
	}
}
