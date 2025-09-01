<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Organisms;

use App\Modules\Gaia\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Shared\Domain\Model\QueueableInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Queues',
	template: 'components/Organisms/Queues.html.twig',
)]
final class Queues
{
	public string $queueComponent;
	public int $speedBonus;
	public int $buildingIdentifier;
	public int $queuesCount;
	/** @var list<QueueableInterface>  */
	public array $queues;
	public int $availableQueuesCount;
	public float $refund;

	public function __construct(
		private readonly CurrentPlayerPlanetsRegistry $currentPlayerBasesRegistry,
		private readonly PlanetHelper                 $planetHelper,
	) {
	}

	public function mount(int $buildingIdentifier, int $buildingLevel): void
	{
		$this->buildingIdentifier = $buildingIdentifier;
		$this->availableQueuesCount = $this->planetHelper->getBuildingInfo(
			$buildingIdentifier,
			'level',
			$buildingLevel,
			'nbQueues',
		);
	}
}
