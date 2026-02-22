<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms;

use App\Modules\Demeter\Model\Color;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'InsetLabelInput',
	template: 'components/Atoms/InsetLabelInput.html.twig',
)]
class InsetLabelInput
{
	public string $name;
	public string $label;
	public string $type = 'text';
	public Color $faction;
	public ?string $placeholder = null;
	public mixed $value = null;
}
