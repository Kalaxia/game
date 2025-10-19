<?php

declare(strict_types=1);

namespace App\Tests\Modules\Economy\Domain\Service;

use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Economy\Domain\Service\SearchMarketOffers;
use App\Modules\Economy\Infrastructure\Foundry\Story\SearchOffersStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

class SearchMarketOffersTest extends KernelTestCase
{
	#[WithStory(SearchOffersStory::class)]
    public function test(): void
    {
		static::bootKernel();
		/** @var SearchMarketOffers $searchMarketOffers */
		$searchMarketOffers = static::getContainer()->get(SearchMarketOffers::class);
		/** @var ProductRepositoryInterface $productRepository */
		$productRepository = static::getContainer()->get(ProductRepositoryInterface::class);

		dd($searchMarketOffers);
    }
}
