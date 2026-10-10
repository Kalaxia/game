<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Atoms;

use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'FleetGridPosition',
	template: 'components/Atoms/Fleet/GridPosition.html.twig',
)]
class FleetGridPosition
{
	use DefaultActionTrait;

	public int $x;
	public int $y;
}
