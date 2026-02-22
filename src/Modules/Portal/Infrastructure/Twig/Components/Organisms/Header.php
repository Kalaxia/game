<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Twig\Components\Organisms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PortalHeader',
	template: 'components/Organisms/Portal/Header.html.twig',
)]
class Header
{
}
