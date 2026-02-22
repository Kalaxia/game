<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms\Layout\Title;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'H5',
	template: 'components/Atoms/Layout/Title/H5.html.twig',
)]
class H5
{
}
