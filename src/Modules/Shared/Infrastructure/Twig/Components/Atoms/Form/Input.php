<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms\Form;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Input',
	template: 'components/Atoms/Form/Input.html.twig',
)]
class Input
{
}
