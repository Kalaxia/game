<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms\Form;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Textarea',
	template: 'components/Atoms/Form/Textarea.html.twig',
)]
class Textarea
{
}
