<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Organisms\Layout;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ContentContainer',
	template: 'components/Organisms/Layout/ContentContainer.html.twig',
)]
final class ContentContainer
{
}
