<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Organisms;

use App\Modules\Demeter\Model\Election\Mandate;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'MandateDashboard',
	template: 'components/Faction/Organisms/MandateDashboard.html.twig'
)]
class MandateDashboard
{
	public Mandate $currentMandate;
}
