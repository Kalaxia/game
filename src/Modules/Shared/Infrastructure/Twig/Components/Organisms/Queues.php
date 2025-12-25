<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Organisms;

use App\Shared\Domain\Model\QueueableInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Queues',
	template: 'components/Organisms/Queues.html.twig',
)]
final class Queues
{
	/** @var list<QueueableInterface>  */
	public array $queues;
	public float $refund;

	public function mount(int $buildingIdentifier, int $buildingLevel): void
	{
	}
}
