<?php

declare(strict_types=1);

namespace App\Modules\Economy\Application\Handler;

use App\Modules\Economy\Application\Message\ProductOfferIndexMessage;
use App\Modules\Economy\Domain\Repository\ProductOfferRepositoryInterface;
use App\Modules\Economy\Domain\Service\HydrateOffer;
use App\Modules\Economy\Domain\Service\SearchMarketOffers;
use App\Modules\Shared\Infrastructure\Meilisearch\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ProductOfferIndexHandler
{
	public function __construct(
		private HydrateOffer $hydrateOffer,
		private ProductOfferRepositoryInterface $productOfferRepository,
		private SearchMarketOffers $searchMarketOffers,
		private SearchService $searchService,
	) {
	}

	public function __invoke(ProductOfferIndexMessage $message): void
	{
		if (null === ($offerData = $this->productOfferRepository->get($message->productId))) {
			throw new \RuntimeException(sprintf('Product offer %s not found', $message->productId));
		}

		$offer = ($this->hydrateOffer)($offerData, $this->searchMarketOffers);

		$this->searchService->store($offer);
	}
}
