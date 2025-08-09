<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Atoms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ShipPicto', template: 'components/Atoms/Ship/ShipPicto.html.twig')]
final class ShipPicto
{
	public int $shipIdentifier;
}
