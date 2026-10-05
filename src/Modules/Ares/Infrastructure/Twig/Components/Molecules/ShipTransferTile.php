<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Molecules;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ShipTransferTile',
	template: 'components/Molecules/Fleet/ShipTransferTile.html.twig'
)]
class ShipTransferTile
{
	public int $identifier;
	public int $quantity;
	public string $side;
	public bool $isInfinite = false;
}
