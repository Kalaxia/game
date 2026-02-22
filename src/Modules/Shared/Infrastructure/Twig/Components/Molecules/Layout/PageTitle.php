<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Layout;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PageTitle',
	template: 'components/Molecules/Layout/PageTitle.html.twig'
)]
class PageTitle
{
}
