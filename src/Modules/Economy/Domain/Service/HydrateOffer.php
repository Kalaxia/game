<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Service;

use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Service\Configuration\GetIndustrySchemasConfiguration;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\CalculateTravelTime;

readonly class HydrateOffer
{
	public function __construct(
		private CalculateTravelTime $calculateTravelTime,
		private GetIndustrySchemasConfiguration $getIndustrySchemasConfiguration,
	) {
	}

	public function __invoke(Offer $offer, SearchMarketOffers $searchMarketOffers): Offer
	{
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
			if ($requirement['product_type'] === ProductType::Component) {
				$componentConfiguration = ($this->getIndustrySchemasConfiguration)(
					'components',
					$requirement['slug']->value,
				);
				$requirementOffer = $searchMarketOffers(
					$offer->systemXPosition,
					$offer->systemYPosition,
					componentProductSlug: $componentConfiguration['slug'],
					limit: 1,
				)[0] ?? throw new \Exception('Missing requirement');

				$offer->craftTime = 6000;
			} elseif ($requirement['product_type'] === ProductType::Resource) {
				$requirementOffer = $searchMarketOffers(
					$offer->systemXPosition,
					$offer->systemYPosition,
					resourceType: $requirement['type'],
					limit: 1,
				)[0] ?? throw new \Exception('Missing requirement');

				$offer->requirementsPrice = 10;
				$offer->craftTime = 300;
			} else {
				throw new \InvalidArgumentException(sprintf(
					'Requirement has wrong schema type: %s.',
					$requirement['product_type'],
				));
			}
			$offer->requirementsPrice += $requirementOffer->getTotalPrice() * $requirement['quantity'];
			$requirementsTravelTimes[] = $requirementOffer->getDeliveryTime();
			$offer->requirementOffers[] = $requirementOffer;
		}

		return $offer;
	}
}
