<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Repository;

use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<Product>
 */
interface ProductRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<Offer>
	 */
	public function searchProducts(
		Activity $activity,
		System $system,
		int $maxDistance = 30,
		int $limit = 20,
		int $offset = 0,
	): array;
}
