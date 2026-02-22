<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms\Layout\Title;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'H6',
	template: 'components/Atoms/Layout/Title/H6.html.twig',
)]
class H6
{
}
