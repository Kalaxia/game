<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms;

use App\Modules\Demeter\Model\Color;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Button',
	template: 'components/Atoms/Button.html.twig',
)]
class Button
{
	public string $type = 'button';
	public Color|null $faction = null;
}
