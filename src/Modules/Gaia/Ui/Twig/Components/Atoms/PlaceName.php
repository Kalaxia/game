<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Ui\Twig\Components\Atoms;

use App\Modules\Gaia\Domain\Entity\Planet;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlaceName',
	template: 'components/Atoms/Map/PlaceName.html.twig',
)]
class PlaceName
{
	public Planet $place;
}
