<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Shared\Application\PercentageApplier;

readonly class CountAffordableShips
{
	public function __construct(private CountHangarAvailableStorableShipPoints $countHangarAvailableStorableShipPoints)
	{
	}

	/**
	 * @param list<ShipQueue> $shipQueues
	 */
	public function __invoke(int $shipIdentifier, OrbitalBase $base, DockType $dockType, array $shipQueues): int
	{
		return min(
			$this->countAffordableShipsFromResources($shipIdentifier, $base),
			$this->countAffordableShipsFromHangarStorage($shipIdentifier, $base, $dockType, $shipQueues),
		);
	}

	/**
	 * @param list<ShipQueue> $shipQueues
	 */
	private function countAffordableShipsFromHangarStorage(
		int $shipIdentifier,
		OrbitalBase $base,
		DockType $dockType,
		array $shipQueues,
	): int {
		$affordableShipPoints = ($this->countHangarAvailableStorableShipPoints)($base, $shipQueues, $dockType);

		$affordableShipsCount = intval(floor($affordableShipPoints / ShipResource::getInfo($shipIdentifier, 'pev')));

		return min($affordableShipsCount, 99);
	}

	private function countAffordableShipsFromResources(int $shipIdentifier, OrbitalBase $base): int
	{
		$resourcePrice = ShipResource::getInfo($shipIdentifier, 'resourcePrice');
		// TODO Apply BonusApplier once faction bonuses are processable with it
		if (ColorResource::KALANKAR === $base->player->faction->identifier && in_array($shipIdentifier, [ShipResource::CERBERE, ShipResource::PHENIX])) {
			$resourcePrice -= PercentageApplier::toInt($resourcePrice, ColorResource::BONUS_EMPIRE_CRUISER);
		}

		$affordableShipsCount = intval(floor($base->resourcesStorage / $resourcePrice));

		return min($affordableShipsCount, 99);
	}
}
