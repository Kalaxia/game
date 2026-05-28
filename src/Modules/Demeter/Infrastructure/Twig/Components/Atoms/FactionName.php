<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Atoms;

use App\Modules\Demeter\Model\Color;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'FactionName',
	template: 'components/Atoms/Faction/FactionName.html.twig',
)]
final class FactionName
{
	public Color $faction;
	public string $type;
}
