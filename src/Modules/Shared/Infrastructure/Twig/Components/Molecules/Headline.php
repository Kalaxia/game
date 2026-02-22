<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Headline',
	template: 'components/Molecules/Headline.html.twig',
)]
final readonly class Headline
{
	public string $title;
	public ?string $subTitle;
}
