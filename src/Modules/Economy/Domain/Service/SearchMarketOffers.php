<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Service;

use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Entity\ComponentProduct;
use App\Modules\Economy\Domain\Entity\PlanetActivity;
use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Economy\Domain\Entity\ResourceProduct;
use App\Modules\Economy\Domain\Entity\ShipProduct;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Economy\Domain\Service\Configuration\GetIndustrySchemasConfiguration;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\CalculateTravelTime;

/**
 * TODO Implement caching for each offers level
 * Get some cache warmers for each planet activity to get requirements offers warmed up
 * Send the refresh via Messenger to avoid slowing down the warming.
 * Get a cache that is refreshed when a planet activity receives a new command.
 * We could add cache tags to know which company has been updated.
 */
readonly class SearchMarketOffers
{
	public function __construct(
		private ProductRepositoryInterface $productRepository,
		private CalculateTravelTime $calculateTravelTime,
		private GetIndustrySchemasConfiguration $getIndustrySchemasConfiguration,
	) {
	}

	/**
	 * @return list<Offer>
	 */
	public function __invoke(
		int                       $systemXPosition,
		int                       $systemYPosition,
		Activity|null             $activity = null,
		ProductType|null          $productType = null,
		ShipProductSlug|null      $shipProductSlug = null,
		ComponentProductSlug|null $componentProductSlug = null,
		ResourceType|null         $resourceType = null,
		int 					  $limit = 20,
		int 					  $offset = 0,
		int                       $maxDistance = 300,
	): array {
		$offers = $this->productRepository->searchOffers(
			systemXPosition: $systemXPosition,
			systemYPosition: $systemYPosition,
			requestedQuantity: 1,
			activity: $activity,
			productType: $productType,
			shipProductSlug: $shipProductSlug,
			componentProductSlug: $componentProductSlug,
			resourceType: $resourceType,
			maxDistance: $maxDistance,
			limit: $limit,
			offset: $offset,
		);

		foreach ($offers as $offer) {
			$productConfiguration = match ($offer->productType) {
				ProductType::Ship => ($this->getIndustrySchemasConfiguration)(
					'ships',
					$offer->shipProductSlug->value,
				),
				ProductType::Component => ($this->getIndustrySchemasConfiguration)(
					'components',
					$offer->componentProductSlug->value,
				),
				ProductType::Resource => null,
			};

			$requirementsTravelTimes = [];

			foreach ($productConfiguration['requirements'] ?? [] as $requirement) {
				if ($productConfiguration['product_type'] === ProductType::Component) {
					$componentConfiguration = ($this->getIndustrySchemasConfiguration)(
						'components',
						$requirement['slug']->value,
					);
					$requirementOffer = $this->__invoke(
						$offer->systemXPosition,
						$offer->systemYPosition,
						componentProductSlug: $componentConfiguration['slug'],
						limit: 1,
					)[0];
					$offer->craftTime = 6000;
				} elseif ($productConfiguration['product_type'] === ProductType::Resource) {
					$requirementOffer = $this->__invoke(
						$offer->systemXPosition,
						$offer->systemYPosition,
						resourceType: $requirement['type'],
						limit: 1,
					)[0];
					$offer->requirementsPrice = 10;
					$offer->craftTime = 300;
				} else {
					throw new \InvalidArgumentException(sprintf(
						'Requirement has wrong schema type: %s.',
						$productConfiguration['product_type'],
					));
				}
				$offer->requirementsPrice += $requirementOffer->getTotalPrice() * $requirement['quantity'];
				$requirementsTravelTimes[] = $requirementOffer->getDeliveryTime();
				$offer->requirementOffers[] = $requirementOffer;
			}
			$offer->travelTime = $this->calculateTravelTime->fromDistance(
				distance: $offer->distance,
				travelType: TravelType::CommercialShipping,
			);
			$offer->requirementsDeliveryTime = count($requirementsTravelTimes) > 0 ? max($requirementsTravelTimes) : 0;
		}

		usort($offers, fn (Offer $a, Offer $b): int => $a->getTotalPrice() <=> $b->getTotalPrice());

		return $offers;
	}

	public function getProductOffer(PlanetActivity $planetActivity, Product $product, int $requestedQuantity = 1): Offer
	{
		if ($product instanceof ShipProduct) {
			$shipCategory = $product->shipCategory;
			$shipProductSlug = $product->slug;
		}

		if ($product instanceof ComponentProduct) {
			$componentProductSlug = $product->slug;
			$componentType = $product->type;
		}

		if ($product instanceof ResourceProduct) {
			$resourceType = $product->slug;
		}

		$offer = new Offer(
			productId: $product->id,
			productType: $product->getType(),
			companyName: $product->company->name,
			resourceType: $resourceType ?? null,
			componentProductSlug: $componentProductSlug ?? null,
			componentType: $componentType ?? null,
			shipCategory: $shipCategory ?? null,
			shipProductSlug: $shipProductSlug ?? null,
			requestedQuantity: $requestedQuantity,
			factionIdentifier: $product->company->faction->identifier,
		);

		return $offer;
	}
}
