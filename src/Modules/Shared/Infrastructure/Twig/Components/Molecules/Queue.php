<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules;

abstract class Queue
{
	public int $refund;
	public ?string $nameComponent = null;

	abstract public function getName(): string;

	abstract public function getSubTitle(): string;

	abstract public function getIdentifier(): int;

	abstract public function getCancelRoute(): string;

	/**
	 * @return array<string, mixed>
	 */
	abstract public function getCancelParameters(): array;

	abstract public function getPicto(): string;

	public function getCurrentTime(): int
	{
		return (new \DateTimeImmutable())->getTimestamp();
	}
}
