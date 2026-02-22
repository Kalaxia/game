<?php

namespace App\Modules\Athena\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Message\Ship\ShipQueueMessage;
use App\Shared\Application\SchedulerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ShipQueueManager implements SchedulerInterface
{
	public function __construct(
		private ShipQueueRepositoryInterface $shipQueueRepository,
		private MessageBusInterface $messageBus,
	) {
	}

	public function schedule(): void
	{
		$queues = $this->shipQueueRepository->getAll();

		foreach ($queues as $queue) {
			$this->messageBus->dispatch(new ShipQueueMessage($queue->id), [DateTimeConverter::to_delay_stamp($queue->getEndDate())]);
		}
	}
}
