<?php

declare(strict_types=1);

namespace App\Modules\Athena\Application\Factory;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Event\NewBuildingQueueEvent;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Building\GetTimeCost;
use App\Modules\Athena\Message\Building\BuildingQueueMessage;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Shared\Application\Handler\DurationHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class BuildingQueueFactory
{
	public function __construct(
		private BuildingQueueRepositoryInterface $buildingQueueRepository,
		private DurationHandler $durationHandler,
		private EventDispatcherInterface $eventDispatcher,
		private GetTimeCost $getTimeCost,
		private MessageBusInterface $messageBus,
	) {
	}

	public function create(
		Planet $planet,
		int $identifier,
		int $targetLevel,
		\DateTimeImmutable $startedAt,
	): BuildingQueue {
		// build the new building
		$buildingQueue = new BuildingQueue(
			id: Uuid::v4(),
			base: $planet,
			buildingNumber: $identifier,
			targetLevel: $targetLevel,
			startedAt: $startedAt,
			endedAt: $this->durationHandler->getDurationEnd($startedAt, ($this->getTimeCost)($identifier, $targetLevel)),
		);

		$this->buildingQueueRepository->save($buildingQueue);

		$this->messageBus->dispatch(
			new BuildingQueueMessage($buildingQueue->id),
			[DateTimeConverter::to_delay_stamp($buildingQueue->getEndDate())],
		);

		$this->eventDispatcher->dispatch(new NewBuildingQueueEvent($buildingQueue));

		return $buildingQueue;
	}
}
