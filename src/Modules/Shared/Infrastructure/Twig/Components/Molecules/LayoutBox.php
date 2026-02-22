<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'LayoutBox',
	template: 'components/Molecules/LayoutBox.html.twig',
)]
final class LayoutBox
{
}
