<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Ui\Twig\Components\Atoms;

use App\Modules\Galaxy\Domain\Entity\Place;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlaceName',
	template: 'components/Atoms/Map/PlaceName.html.twig',
)]
class PlaceName
{
	public Place $place;
}
