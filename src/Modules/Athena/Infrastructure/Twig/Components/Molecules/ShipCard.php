<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Molecules;

use App\Modules\Athena\Domain\Service\Base\Ship\CountShipResourceCost;
use App\Modules\Athena\Domain\Service\Base\Ship\CountShipTimeCost;
use App\Modules\Athena\Helper\ShipHelper;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Card;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ShipCard',
	template: 'components/Molecules/Base/ShipCard.html.twig',
)]
class ShipCard extends Card
{
	public int $shipIdentifier;
	public string|null $missingTechnology = null;
	public string|bool $hasTechnologyRequirements;
	public string|bool $hasShipQueueRequirements;
	public string|bool $hasShipTreeRequirements;
	public int $resourceCost;
	public int $timeCost;

	public function __construct(
		private readonly CountShipResourceCost $countShipResourceCost,
		private readonly CountShipTimeCost     $countShipTimeCost,
		private readonly ShipHelper            $shipHelper,
	) {
	}

	/**
	 * @param list<\App\Modules\Athena\Model\ShipQueue> $shipQueues
	 */
	public function mount(int $shipIdentifier, Planet $planet, Technology $technology, array $shipQueues, int $queuesCount): void
	{
		$this->shipIdentifier = $shipIdentifier;

		$technologyRights = $this->shipHelper->haveRights($shipIdentifier, 'techno', $technology);
		$this->hasTechnologyRequirements = $technologyRights;
		$this->missingTechnology = (true !== $technologyRights) ? $technologyRights : null;
		$this->hasShipTreeRequirements = $this->shipHelper->haveRights($shipIdentifier, 'shipTree', $planet);
		$this->hasShipQueueRequirements = $this->shipHelper->haveRights($shipIdentifier, 'queue', $planet, $queuesCount);

		$this->resourceCost = ($this->countShipResourceCost)($this->shipIdentifier, 1);
		$this->timeCost = ($this->countShipTimeCost)($this->shipIdentifier, 1);
	}
}
