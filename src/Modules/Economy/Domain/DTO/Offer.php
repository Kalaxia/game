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
use App\Modules\Shared\Domain\Model\Searchable;
use Symfony\Component\Uid\Uuid;

class Offer implements Searchable
{
	public int $requirementsPrice = 0;
	public int $craftTime = 0;
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
		public int                       $systemXPosition,
		public int                       $systemYPosition,
		public int                       $sectorIdentifier,
		public int 						 $margin = 20,
	) {
	}

	public static function getIndex(): string
	{
		return 'trade_offers';
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

	public function getSearchableData(): array
	{
		return [
			'requirementsPrice' => $this->requirementsPrice,
			'craftTime' => $this->craftTime,
			'requirementOffers' => array_map(
				fn (Offer $offer) => $offer->productId->toRfc4122(),
				$this->requirementOffers,
			),
			'productId' => $this->productId->toRfc4122(),
			'productType' => $this->productType,
			'componentType' => $this->componentType,
			'componentProductSlug' => $this->componentProductSlug,
			'resourceType' => $this->resourceType,
			'shipCategory' => $this->shipCategory,
			'shipProductSlug' => $this->shipProductSlug,
			'companyName' => $this->companyName,
			'requestedQuantity' => $this->requestedQuantity,
			'factionIdentifier' => $this->factionIdentifier,
			'sectorIdentifier' => $this->sectorIdentifier,
			'margin' => $this->margin,
//			'_geo' => [
//				'lat' => $this->systemXPosition,
//				'lng' => $this->systemYPosition,
//			],
			'_geojson' => [
				'type' => 'Feature',
				'geometry' => [
					'type' => 'Point',
					'coordinates' => [$this->systemXPosition, $this->systemYPosition],
				],
			],
		];
	}

	public static function getIndexSettings(): array
	{
		return [
			'filterableAttributes' => [
				'resourceType',
				'componentType',
				'componentProductSlug',
				'shipProductSlug',
				'shipCategory',
				'productType',
				'_geo',
				'_geojson',
			],
			'sortableAttributes' => [
				'_geo',
			],
		];
	}
}
