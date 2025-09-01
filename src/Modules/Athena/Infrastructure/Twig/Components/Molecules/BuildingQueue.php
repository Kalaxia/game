<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Molecules;

use App\Modules\Athena\Infrastructure\Controller\Base\Building\Cancel;
use App\Modules\Athena\Model\BuildingQueue as BuildingQueueModel;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Queue;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'BuildingQueue',
	template: 'components/Molecules/Queue.html.twig'
)]
final class BuildingQueue extends Queue
{
	public BuildingQueueModel|null $queue = null;

	public function __construct(
		private readonly DurationHandler $durationHandler,
		private readonly PlanetHelper    $planetHelper,
		#[Autowire('%athena.building.building_queue_resource_refund%')]
		public readonly float            $buildingResourceRefund,
	) {

	}

	public function getName(): string
	{
		if (null === $this->queue) {
			throw new \LogicException('You cannot use name property on an empty queue');
		}

		return $this->planetHelper->getBuildingInfo($this->queue->buildingNumber, 'frenchName');
	}

	public function getSubTitle(): string
	{
		if (null === $this->queue) {
			throw new \LogicException('You cannot use name property on an empty queue');
		}

		return sprintf('niv. %s', $this->queue->targetLevel);
	}

	public function getCancelRoute(): string
	{
		return Cancel::ROUTE_NAME;
	}

	public function getCancelParameters(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
		];
	}

	public function getPicto(): string
	{
		return '';
	}

	public function getIdentifier(): int
	{
		return $this->queue->buildingNumber
			?? throw new \LogicException('You cannot use identifier property on an empty queue');
	}
}
