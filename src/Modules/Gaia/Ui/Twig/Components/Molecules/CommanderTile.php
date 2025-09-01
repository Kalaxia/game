<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Ui\Twig\Components\Molecules;

use App\Modules\Gaia\Domain\Entity\Place;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CommanderTile',
	template: 'components/Molecules/Map/CommanderTile.html.twig',
)]
class CommanderTile
{
	public Place $place;
	public string $actionUrl;
	public string $travelTime;
}
