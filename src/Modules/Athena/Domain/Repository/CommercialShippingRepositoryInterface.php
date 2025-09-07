<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

interface CommercialShippingRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): CommercialShipping|null;

	public function getByTransaction(Transaction $transaction): CommercialShipping|null;

	/**
	 * @return list<CommercialShipping>
	 */
	public function getAll(): array;

	/**
	 * @return list<CommercialShipping>
	 */
	public function getMoving(): array;

	/**
	 * @return list<CommercialShipping>
	 */
	public function getByPlanet(Planet $planet): array;
}
