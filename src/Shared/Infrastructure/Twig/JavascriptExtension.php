<?php

namespace App\Shared\Infrastructure\Twig;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class JavascriptExtension extends AbstractExtension
{
	public function __construct(
		private readonly RequestStack $requestStack,
		private readonly GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		private readonly TranslatorInterface $translator,
	) {
	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			new TwigFunction('get_api_endpoint', fn () => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost()),
			new TwigFunction('get_ship_names', fn () => $this->getShipNames()),
			new TwigFunction('get_ship_pevs', fn () => $this->getShipPevs()),
		];
	}

	protected function getShipNames(): array
	{
		return array_map(
			fn (ShipCategory $shipCategory) => $this->translator->trans('ship_categories.'.$shipCategory->value.'.name'),
			ShipCategory::cases(),
		);
	}

	protected function getShipPevs(): array
	{
		return array_map(
			fn (ShipCategory $shipCategory) => ($this->getShipCategoriesConfiguration)($shipCategory, 'pev'),
			ShipCategory::cases(),
		);
	}
}
