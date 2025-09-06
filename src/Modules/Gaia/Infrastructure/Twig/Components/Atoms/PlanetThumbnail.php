<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\Twig\Components\Atoms;

use App\Modules\Gaia\Domain\Entity\Planet;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlanetThumbnail',
	template: 'components/Atoms/Map/PlanetThumbnail.html.twig',
)]
class PlanetThumbnail
{
	public Planet $planet;
}
