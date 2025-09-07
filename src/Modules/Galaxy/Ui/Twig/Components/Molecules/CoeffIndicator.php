<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Ui\Twig\Components\Molecules;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CoeffIndicator',
	template: 'components/Molecules/Map/CoeffIndicator.html.twig',
)]
class CoeffIndicator
{
	public string $label;
	/** @var list<int|float> */
	public array $values;
	public int|float $coeff;
	public string $mediaPath;
}
