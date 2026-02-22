<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Form;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'FormStep',
	template: 'components/Molecules/Form/Step.html.twig',
)]
class Step
{
	public int $number;
	public string $status;
	public string $label;
	public bool $isLast = false;

	public function getStepClass(): string
	{
		return match ($this->status) {
			'done' => 'bg-stone-200 group-hover:bg-stone-200',
			'current' => 'border-2 border-stone-200',
			'next' => 'border-2 border-gray-300 group-hover:border-gray-400',
		};
	}

	public function getLabelClass(): string
	{
		return match ($this->status) {
			'done' => 'text-gray-900',
			'current' => 'text-stone-200',
			'next' => 'text-gray-500 group-hover:text-gray-600',
		};
	}
}
