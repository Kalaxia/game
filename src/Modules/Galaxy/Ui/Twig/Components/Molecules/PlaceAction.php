<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Ui\Twig\Components\Molecules;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlaceAction',
	template: 'components/Molecules/Map/PlaceAction.html.twig',
)]
class PlaceAction
{
	public string $name = '';
	public string $picto;
	public string $tooltip = '';
}
