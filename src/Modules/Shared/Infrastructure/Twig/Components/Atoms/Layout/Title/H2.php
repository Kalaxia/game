<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms\Layout\Title;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'H2',
	template: 'components/Atoms/Layout/Title/H2.html.twig',
)]
class H2
{
}
