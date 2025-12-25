<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Organisms;

use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Model\ShipQueue;
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
		private readonly TechnologyQueueRepositoryInterface $technologyQueueRepository,
		private readonly ShipQueueRepositoryInterface $shipQueueRepository,
		#[Autowire('%athena.building.building_queue_resource_refund%')]
		public float $buildingQueueResourceRefund,
	) {
	}

	public function mount(Planet $planet): void
	{
		$this->planet = $planet;
		$this->technologyQueues = $this->technologyQueueRepository->getPlanetQueues($planet);
		$this->dock1ShipQueues = $this->shipQueueRepository->getByBaseAndDockType($planet, 1);
		$this->dock2ShipQueues = $this->shipQueueRepository->getByBaseAndDockType($planet, 2);
	}
}
