<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Organisms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ElectionDashboard',
	template: 'components/Faction/Organisms/ElectionDashboard.html.twig',
)]
class ElectionDashboard
{

}
