<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\Twig\Components\Atoms;

use App\Modules\Galaxy\Domain\Entity\Planet;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlanetThumbnail',
	template: 'components/Atoms/Map/PlanetThumbnail.html.twig',
)]
class PlanetThumbnail
{
	public Planet $planet;
}
