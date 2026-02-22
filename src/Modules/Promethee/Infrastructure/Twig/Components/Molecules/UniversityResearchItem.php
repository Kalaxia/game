<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Infrastructure\Twig\Components\Molecules;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'UniversityResearchItem',
	template: 'components/Molecules/Technology/UniversityResearchItem.html.twig',
)]
class UniversityResearchItem
{
}
