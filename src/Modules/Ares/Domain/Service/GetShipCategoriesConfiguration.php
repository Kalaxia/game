<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service;

use App\Modules\Ares\Domain\Model\ShipCategory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

class GetShipCategoriesConfiguration
{
	public function __construct(
		private TranslatorInterface $translator,
		#[Autowire('%app.ship_categories%')]
		private array $shipCategoriesConfiguration,
	) {
	}

	public function __invoke(ShipCategory|int $shipCategory, string $info): mixed
	{
		$identifier = ($shipCategory instanceof ShipCategory) ? $shipCategory->value : $shipCategory;

		return $this->shipCategoriesConfiguration[$identifier][$info]
			?? $this->tryToTranslate($identifier, $info)
			?? throw new \InvalidArgumentException(sprintf('Statistic %s not found for ShipCategory %d', $info, $identifier));
	}

	#[\Deprecated(message: 'Use the translator directly instead of this method')]
	private function tryToTranslate(int $identifier, string $info): array|string|null
	{
		return $this->translator->trans(sprintf('ship_categories.%d.%s', $identifier, $info));
	}
}
