<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Shared\Application\PercentageApplier;

readonly class CountAffordableShips
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		private CountHangarAvailableStorableShipPoints $countHangarAvailableStorableShipPoints,
	) {
	}

	/**
	 * @param list<ShipQueue> $shipQueues
	 */
	public function __invoke(int $shipIdentifier, Planet $planet, DockType $dockType, array $shipQueues): int
	{
		return min(
			$this->countAffordableShipsFromResources($shipIdentifier, $planet),
			$this->countAffordableShipsFromHangarStorage($shipIdentifier, $planet, $dockType, $shipQueues),
		);
	}

	/**
	 * @param list<ShipQueue> $shipQueues
	 */
	private function countAffordableShipsFromHangarStorage(
        int      $shipIdentifier,
        Planet   $planet,
        DockType $dockType,
        array    $shipQueues,
	): int {
		$affordableShipPoints = ($this->countHangarAvailableStorableShipPoints)($planet, $shipQueues, $dockType);

		$affordableShipsCount = intval(floor($affordableShipPoints / ($this->getShipCategoriesConfiguration)($shipIdentifier, 'pev')));

		return min($affordableShipsCount, 99);
	}

	private function countAffordableShipsFromResources(int $shipIdentifier, Planet $planet): int
	{
		$resourcePrice = ($this->getShipCategoriesConfiguration)($shipIdentifier, 'resourcePrice');
		// TODO Apply BonusApplier once faction bonuses are processable with it
		if (ColorResource::KALANKAR === $planet->player->faction->identifier && in_array($shipIdentifier, [ShipCategory::Cruiser, ShipCategory::HeavyCruiser])) {
			$resourcePrice -= PercentageApplier::toInt($resourcePrice, ColorResource::BONUS_EMPIRE_CRUISER);
		}

		$affordableShipsCount = intval(floor($planet->resourcesStorage / $resourcePrice));

		return min($affordableShipsCount, 99);
	}
}
