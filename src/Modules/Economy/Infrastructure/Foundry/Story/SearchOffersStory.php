<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Foundry\Story;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Economy\Infrastructure\Foundry\Factory\ShipProductFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'search_offers', groups: ['test_env'])]
class SearchOffersStory extends Story
{
	public function build(): void
	{
		FactionFactory::randomOrCreate();

		ShipProductFactory::createSequence(array_map(
			fn (ShipCategory $shipCategory) => [
				'shipCategory' => $shipCategory,
			],
			ShipCategory::cases(),
		));
	}
}
