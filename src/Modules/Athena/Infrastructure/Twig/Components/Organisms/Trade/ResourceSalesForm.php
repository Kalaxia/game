<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Organisms\Trade;

use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\Transaction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
	name: 'ResourceSalesForm',
	template: 'components/Organisms/Trade/ResourceSalesForm.html.twig',
)]
final class ResourceSalesForm
{
	use DefaultActionTrait;

	#[LiveProp(writable: true)]
	public ?int $quantity = null;
	public bool $previousSuccess = false;

	public function getMinPrice(): ?int
	{
		return $this->quantity > 0 ? intval(ceil($this->quantity * Transaction::MIN_RATE_RESOURCE)) : null;
	}

	public function getRequiredShipsCount(): ?int
	{
		return $this->quantity > 0 ? intval(ceil($this->quantity / CommercialShipping::WEDGE)) : null;
	}
}
