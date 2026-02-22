<?php

namespace App\Modules\Promethee\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Message\TechnologyQueueMessage;
use App\Shared\Application\SchedulerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class TechnologyQueueManager implements SchedulerInterface
{
	public function __construct(
		private TechnologyQueueRepositoryInterface $technologyQueueRepository,
		private MessageBusInterface $messageBus,
	) {
	}

	public function schedule(): void
	{
		$queues = $this->technologyQueueRepository->getAll();

		foreach ($queues as $queue) {
			$this->messageBus->dispatch(
				new TechnologyQueueMessage($queue->id),
				[DateTimeConverter::to_delay_stamp($queue->getEndDate())],
			);
		}
	}
}
