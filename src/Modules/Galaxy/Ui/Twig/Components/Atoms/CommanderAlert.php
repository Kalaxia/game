<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Ui\Twig\Components\Atoms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CommanderAlert',
	template: 'components/Atoms/Map/CommanderAlert.html.twig',
)]
class CommanderAlert
{
	public string $identifier;
	public int $factionIdentifier;
}
