<?php

declare(strict_types=1);

namespace App\Modules\Athena\Application\Factory;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Domain\Event\NewShipQueueEvent;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Ship\CountShipTimeCost;
use App\Modules\Athena\Message\Ship\ShipQueueMessage;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Shared\Application\Handler\DurationHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class ShipQueueFactory
{
	public function __construct(
		private DurationHandler              $durationHandler,
		private EventDispatcherInterface     $eventDispatcher,
		private CountShipTimeCost            $countShipTimeCost,
		private MessageBusInterface          $messageBus,
		private ShipQueueRepositoryInterface $shipQueueRepository,
	) {
	}

	public function create(
        Planet             $planet,
        int                $shipIdentifier,
        DockType           $dockType,
        int                $quantity,
        \DateTimeImmutable $startedAt,
	): ShipQueue {

		$shipQueue = new ShipQueue(
			id: Uuid::v4(),
			base: $planet,
			startedAt: $startedAt,
			endedAt: $this->durationHandler->getDurationEnd($startedAt, ($this->countShipTimeCost)(
				identifier: $shipIdentifier,
				dockType: $dockType,
				quantity: $quantity,
			)),
			dockType: $dockType->getIdentifier(),
			shipNumber: $shipIdentifier,
			quantity: $quantity,
		);

		$this->shipQueueRepository->save($shipQueue);

		$this->messageBus->dispatch(
			new ShipQueueMessage($shipQueue->id),
			[DateTimeConverter::to_delay_stamp($shipQueue->getEndDate())],
		);

		$this->eventDispatcher->dispatch(new NewShipQueueEvent($shipQueue));

		return $shipQueue;
	}
}
