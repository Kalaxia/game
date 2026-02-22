<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Organisms\Layout;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ResourceStorage',
	template: 'components/Organisms/Layout/ResourceStorage.html.twig'
)]
class ResourceStorage
{
}
