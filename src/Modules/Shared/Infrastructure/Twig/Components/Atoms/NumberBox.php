<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms;

use App\Modules\Demeter\Model\Color;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'NumberBox',
	template: 'components/Atoms/NumberBox.html.twig',
)]
final class NumberBox
{
	public string $label;
	public string $size = 'big';
	public ?float $bonus = null;
	public float|int|null $percent = null;
	public ?Color $faction = null;

	public function getNumberSize(): string
	{
		return match ($this->size) {
			'big' => 'md:text-3xl/10',
			'small' => 'md:text-lg',
			default => throw new \InvalidArgumentException(sprintf('Invalid size: %s', $this->size)),
		};
	}

	public function getBoxSize(): string
	{
		return match ($this->size) {
			'big' => 'md:gap-x-4 md:gap-y-2 md:p-2 lg:px-4 ',
			'small' => 'md:gap-2 md:p-2',
			default => throw new \InvalidArgumentException(sprintf('Invalid size: %s', $this->size)),
		};
	}
}
