<?php

namespace App\Shared\Infrastructure\Twig;

use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Shared\Application\Handler\DurationHandler;
use App\Shared\Domain\Model\QueueableInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

// @TODO implement the rest of the generic methods and migrate ship logic
class QueueableExtension extends AbstractExtension
{
	public function __construct(
		private readonly TechnologyHelper               $technologyHelper,
		private readonly DurationHandler                $durationHandler,
		private readonly GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			new TwigFunction('get_technology_info', fn (int $technology, string $info, int $level = 0) => $this->technologyHelper->getInfo($technology, $info, $level)),
		];
	}

	#[\Override]
    public function getFilters(): array
	{
		return [
			// @TODO migrate to a technology twig extension
			new TwigFilter('is_unblocking_technology', fn (TechnologyQueue $technologyQueue) => (!$this->technologyHelper->isAnUnblockingTechnology($technologyQueue->technology))),
			new TwigFilter('queue_duration', fn (QueueableInterface $queue) => $this->durationHandler->getDurationRemainingTime($queue)),
			new TwigFilter('queue_picture', fn (QueueableInterface $queue) => $this->getQueueHelper($queue)->getInfo($queue->getResourceIdentifier(), 'imageLink')),
			new TwigFilter('queue_name', fn (QueueableInterface $queue) => $this->getQueueHelper($queue)->getInfo($queue->getResourceIdentifier(), $this->getQueueNameKey($queue))),
			new TwigFilter('technology_queue_time', fn (TechnologyQueue $technologyQueue) => $this->technologyHelper->getInfo(
				$technologyQueue->technology,
				'time',
				$technologyQueue->targetLevel
			)),
			new TwigFilter('ship_queue_time', fn (ShipQueue $shipQueue) => $shipQueue->quantity * ($this->getShipCategoriesConfiguration)($shipQueue->shipNumber, 'time')),
			new TwigFilter('ship_queue_picture', fn (ShipQueue $shipQueue) => ($this->getShipCategoriesConfiguration)($shipQueue->shipNumber, 'picto')),
			new TwigFilter('ship_queue_name', fn (ShipQueue $shipQueue) => ($this->getShipCategoriesConfiguration)($shipQueue->shipNumber, 'name')),
		];
	}

	protected function getQueueHelper(QueueableInterface $queueable): TechnologyHelper
	{
		return match ($queueable::class) {
			TechnologyQueue::class => $this->technologyHelper,
		};
	}

	protected function getQueueNameKey(QueueableInterface $queueable): string
	{
		return match ($queueable::class) {
			TechnologyQueue::class => 'name',
		};
	}
}
