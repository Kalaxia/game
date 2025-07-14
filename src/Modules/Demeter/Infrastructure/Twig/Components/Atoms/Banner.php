<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Atoms;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
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

	public function __construct(private readonly GetFactionsConfiguration $getFactionsConfiguration)
	{
	}

	public function getBannerFile(): string
	{
		return match ($this->faction->identifier) {
			ColorResource::KALANKAR => 'Adranites.png',
			ColorResource::FALKIRR => 'Dores_Blason_500px.png',
			default => 'Azures_Blason_500px.png',
		};
	}

	public function getName(): string
	{
		return ($this->getFactionsConfiguration)($this->faction, 'officialName');
	}
}
