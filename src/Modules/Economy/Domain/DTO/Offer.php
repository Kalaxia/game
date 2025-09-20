<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\DTO;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use Symfony\Component\Uid\Uuid;

class Offer
{
	public int $price;
	public int $travelTime;

	public function __construct(
		public Uuid $productId,
		ProductType $productType,
		public ComponentType|null $componentType,
		public ResourceType|null $resourceType,
		public ShipCategory|null $shipCategory,
		public string $companyName,
		public ?int $factionIdentifier,
		public int $distance,
		public int $sectorIdentifier,
	) {
	}
}
