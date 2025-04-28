<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Atoms;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'FactionBanner',
	template: 'components/Faction/Atoms/Banner.html.twig',
)]
class Banner
{
	public Color $faction;

	public function getBannerFile(): string
	{
		return match ($this->faction->identifier) {
			ColorResource::EMPIRE => 'Adranites.png',
			ColorResource::NEGORA => 'Dores_Blason_500px.png',
			default => 'Azures_Blason_500px.png',
		};
	}

	public function getName(): string
	{
		return ColorResource::getOfficialName($this->faction);
	}
}
