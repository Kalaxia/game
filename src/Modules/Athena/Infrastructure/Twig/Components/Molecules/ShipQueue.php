<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Molecules;

use App\Modules\Athena\Infrastructure\Controller\Ship\CancelQueue;
use App\Modules\Athena\Model\ShipQueue as ShipQueueModel;
use App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Queue;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ShipQueue',
	template: 'components/Molecules/Queue.html.twig'
)]
class ShipQueue extends Queue
{
	public ?ShipQueueModel $queue = null;
	public ?string $nameComponent = 'ShipQueueName';

	public function getSubTitle(): string
	{
		return '';
	}

	public function getCancelRoute(): string
	{
		return CancelQueue::ROUTE_NAME;
	}

	public function getCancelParameters(): array
	{
		return [
			'id' => $this->queue->id,
		];
	}

	public function getIdentifier(): int
	{
		return $this->queue->shipNumber
			?? throw new \LogicException('You cannot use identifier property on an empty queue');
	}

	public function getName(): string
	{
		throw new \LogicException('This method is deprecated, use translation instead');
	}

	public function getPicto(): string
	{
		throw new \LogicException('This method is deprecated, use configuration instead');
	}
}
