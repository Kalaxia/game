<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Organisms;

use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Repository\BuildingQueueRepository;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Model\TechnologyQueue;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'FastView',
	template: 'components/Organisms/Base/FastView.html.twig',
)]
class FastView
{
	public Planet $planet;
	/** @var list<BuildingQueue> */
	public array $buildingQueues;
	/** @var list<TechnologyQueue> */
	public array $technologyQueues;
	/** @var list<ShipQueue> */
	public array $dock1ShipQueues;
	/** @var list<ShipQueue> */
	public array $dock2ShipQueues;
	/** @var array<string, mixed> */
	public array $commercialRoutesData;
	public int $index;
	public bool $isProfileView;

	public function __construct(
		private readonly BuildingQueueRepository $buildingQueueRepository,
		private readonly TechnologyQueueRepositoryInterface $technologyQueueRepository,
		private readonly ShipQueueRepositoryInterface $shipQueueRepository,
		private readonly CommercialRouteManager $commercialRouteManager,
		#[Autowire('%athena.building.building_queue_resource_refund%')]
		public float $buildingQueueResourceRefund,
	) {
	}

	public function mount(Planet $planet): void
	{
		$this->planet = $planet;
		$this->buildingQueues = $this->buildingQueueRepository->getPlanetQueues($planet);
		$this->technologyQueues = $this->technologyQueueRepository->getPlanetQueues($planet);
		$this->dock1ShipQueues = $this->shipQueueRepository->getByBaseAndDockType($planet, 1);
		$this->dock2ShipQueues = $this->shipQueueRepository->getByBaseAndDockType($planet, 2);

		// @TODO: move it to the using part of the code and remove useless data
		if ($planet->levelSpatioport > 0) {
			$this->commercialRoutesData = $this->commercialRouteManager->getBaseCommercialData($planet);
		}
	}
}
