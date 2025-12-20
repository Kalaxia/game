<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Service;

use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Shared\Infrastructure\Meilisearch\SearchService;

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
		private HydrateOffer $hydrateOffer,
		private ProductRepositoryInterface $productRepository,
		private SearchService $searchService,
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
		$offers = $this->searchService->search(
			index: Offer::getIndex(),
			params: [

			],
		);


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

		foreach ($offers as $offerIndex => $offer) {
			try {
				$offers[$offerIndex] = ($this->hydrateOffer)($offer, $this);
			} catch (\Throwable $exception) {
				unset($offers[$offerIndex]);
			}
		}

		usort($offers, fn (Offer $a, Offer $b): int => $a->getTotalPrice() <=> $b->getTotalPrice());

		return $offers;
	}
}
