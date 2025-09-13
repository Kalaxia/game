<?php

namespace App\Modules\Shared\Domain\Repository;

/**
 * @template T of object
 */
interface EntityRepositoryInterface
{
	/**
	 * @param T $entity
	 */
	public function save(object $entity, bool $doFlush = true): void;

	/**
	 * @param T $entity
	 */
	public function remove(object $entity, bool $doFlush = true): void;

	/**
	 * @param T $entity
	 */
	public function detach(object $entity): void;

	/**
	 * @param T $entity
	 */
	public function refresh(object $entity): void;
}
