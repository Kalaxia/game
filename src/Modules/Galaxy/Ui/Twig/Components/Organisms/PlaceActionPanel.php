<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Ui\Twig\Components\Organisms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlaceActionPanel',
	template: 'components/Organisms/Map/PlaceActionPanel.html.twig',
)]
class PlaceActionPanel
{
	public int $id;
	public string $title;
}
