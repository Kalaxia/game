<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms\Layout\Title;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'H4',
	template: 'components/Atoms/Layout/Title/H4.html.twig',
)]
class H4
{
}
