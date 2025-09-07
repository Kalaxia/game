<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Ui\Twig\Components\Atoms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'TravelDetail',
	template: 'components/Atoms/Map/TravelDetail.html.twig',
)]
class TravelDetail
{
	public string|null $labelId = '';
	public string|null $label = '';
	public string|null $valueId = '';
	public string|null $value = '';
}
