<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Repository;

use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Product>
 */
interface ProductRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Product|null;

	/**
	 * @return list<Offer>
	 */
	public function searchOffers(
		int                       $systemXPosition,
		int                       $systemYPosition,
		int                       $requestedQuantity,
		Activity|null             $activity = null,
		ProductType|null          $productType = null,
		ShipProductSlug|null      $shipProductSlug = null,
		ComponentProductSlug|null $componentProductSlug = null,
		ResourceType|null         $resourceType = null,
		int                       $maxDistance = 30,
		int                       $limit = 20,
		int                       $offset = 0,
	): array;
}
