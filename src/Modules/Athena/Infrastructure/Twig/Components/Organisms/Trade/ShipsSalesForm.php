<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Organisms\Trade;

use App\Modules\Athena\Model\CommercialShipping;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
	name: 'ShipsSalesForm',
	template: 'components/Organisms/Trade/ShipsSalesForm.html.twig',
)]
class ShipsSalesForm
{
	use DefaultActionTrait;

	#[LiveProp(writable: true)]
	public ?int $quantity = null;
	#[LiveProp]
	public int $shipQuantity;
	#[LiveProp]
	public int $shipIdentifier;
	#[LiveProp]
	public $currentRate;

	public function getMinPrice(int $minPrice): int
	{
		return intval(ceil($this->quantity * $minPrice));
	}

	public function getRequiredShipsCount($rate): ?int
	{
		return $this->quantity > 0 ? intval(ceil($this->quantity / CommercialShipping::WEDGE)) : null;
	}
}
