<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\DTO;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use App\Modules\Shared\Application\PercentageApplier;
use Symfony\Component\Uid\Uuid;

class Offer
{
	public int $requirementsPrice = 0;
	public int $travelTime = 0;
	public int $craftTime = 0;
	public int $requirementsDeliveryTime = 0;
	public array $requirementOffers = [];

	public function __construct(
		public Uuid                      $productId,
		public ProductType               $productType,
		public ComponentType|null        $componentType,
		public ComponentProductSlug|null $componentProductSlug,
		public ResourceType|null         $resourceType,
		public ShipCategory|null         $shipCategory,
		public ShipProductSlug|null      $shipProductSlug,
		public string                    $companyName,
		public int                       $requestedQuantity,
		public ?int                      $factionIdentifier,
		public int                       $distance,
		public int                       $systemXPosition,
		public int                       $systemYPosition,
		public int                       $sectorIdentifier,
		public int 						 $margin = 20,
	) {
	}

	public function getCraftPrice(): int
	{
		return $this->requirementsPrice * $this->requestedQuantity;
	}

	public function getTotalPrice(): int
	{
		$craftPrice = $this->getCraftPrice();

		return $craftPrice + PercentageApplier::toInt($craftPrice, $this->margin);
	}
	public function getDeliveryTime(): int
	{
		return $this->requirementsDeliveryTime + $this->travelTime + ($this->craftTime * $this->requestedQuantity);
	}
}
