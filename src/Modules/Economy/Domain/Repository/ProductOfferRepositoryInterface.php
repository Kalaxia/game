<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Repository;

use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use Symfony\Component\Uid\Uuid;

interface ProductOfferRepositoryInterface
{
	/**
	 * @param Uuid $id The product ID
	 * @return Offer|null
	 */
	public function get(Uuid $id): Offer|null;

	/**
	 * @return list<Offer>
	 */
	public function search(
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
