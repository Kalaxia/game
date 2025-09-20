<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Service;

use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\CalculateTravelTime;

readonly class SearchMarketOffers
{
	public function __construct(
		private ProductRepositoryInterface $productRepository,
		private CalculateTravelTime $calculateTravelTime,
	) {
	}

	public function __invoke(
		Activity $activity,
		System $system,
		int $maxDistance = 30,
	): array {
		$products = $this->productRepository->searchProducts(
			$activity,
			$system,
			300,
		);

		foreach ($products as $product) {
			$product->travelTime = $this->calculateTravelTime->fromDistance(
				distance: $product->distance,
				travelType: TravelType::CommercialShipping,
			);
			$product->price = 100;
		}

		return $products;
	}
}
