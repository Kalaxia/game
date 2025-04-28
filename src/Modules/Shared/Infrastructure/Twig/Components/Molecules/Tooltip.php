<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Tooltip',
	template: 'components/Molecules/Tooltip.html.twig',
)]
final class Tooltip
{

}
