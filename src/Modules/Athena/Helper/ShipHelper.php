<?php

namespace App\Modules\Athena\Helper;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Ship\CountHangarAvailableStorableShipPoints;
use App\Modules\Athena\Domain\Service\Base\Ship\CountMaxShipQueues;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;

readonly class ShipHelper
{
	public function __construct(
		private CountMaxShipQueues $countMaxShipQueues,
		private CountHangarAvailableStorableShipPoints $countHangarAvailableStorableShipPoints,
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private TechnologyHelper $technologyHelper,
		private OrbitalBaseHelper $orbitalBaseHelper,
		private ShipQueueRepositoryInterface $shipQueueRepository,
	) {
	}

	/**
	 * TODO Refactor with Specification Pattern
	 */
	public function haveRights(int $shipId, string $type, $sup, int $quantity = 1): bool|string
	{
		if (ShipResource::isAShip($shipId)) {
			switch ($type) {
				// assez de ressources pour construire ?
				case 'resource':
					$price = ShipResource::getInfo($shipId, 'resourcePrice') * $quantity;
					if (
						ColorResource::KALANKAR === $this->currentPlayerRegistry->get()->faction->identifier
						&& in_array($shipId, [ShipResource::CERBERE, ShipResource::PHENIX])
					) {
						$price -= PercentageApplier::toInt($price, ColorResource::BONUS_EMPIRE_CRUISER);
					}

					return !($sup < $price);
				case 'queue':
					return $quantity < ($this->countMaxShipQueues)(
						orbitalBase: $sup,
						dockType: DockType::fromShipIdentifier($shipId),
					);
					// droit de construire le vaisseau ?
					// $sup est un objet de type OrbitalBase
				case 'shipTree':
					if (ShipResource::isAShipFromDock1($shipId)) {
						$level = $sup->levelDock1;

						return $shipId < $this->orbitalBaseHelper->getBuildingInfo(2, 'level', $level, 'releasedShip');
					} elseif (ShipResource::isAShipFromDock2($shipId)) {
						$level = $sup->levelDock2;

						return ($shipId - 6) < $this->orbitalBaseHelper->getBuildingInfo(3, 'level', $level, 'releasedShip');
					} else {
						$level = $sup->levelDock3;

						return ($shipId - 12) < $this->orbitalBaseHelper->getBuildingInfo(4, 'level', $level, 'releasedShip');
					}
				// assez de pev dans le storage et dans la queue ?
				// $sup est un objet de type OrbitalBase
				case 'pev':
					$dockType = DockType::fromShipIdentifier($shipId);
					$wanted = ShipResource::getInfo($shipId, 'pev') * $quantity;

					$shipQueues = $this->shipQueueRepository->getByBaseAndDockType($sup, $dockType->getIdentifier());

					return $wanted <= ($this->countHangarAvailableStorableShipPoints)($sup, $shipQueues, $dockType);
				// a la technologie nécessaire pour constuire ce vaisseau ?
				// $sup est un objet de type Technology
				case 'techno':
					if (1 == $sup->getTechnology(ShipResource::getInfo($shipId, 'techno'))) {
						return true;
					}
					return 'il vous faut développer la technologie ' . $this->technologyHelper->getInfo(ShipResource::getInfo($shipId, 'techno'), 'name');
				default:
					throw new \ErrorException('type invalide dans haveRights de ShipResource');
			}
		} else {
			throw new \ErrorException('shipId invalide (entre 0 et 14) dans haveRights de ShipResource');
		}
	}

	public function dockLevelNeededFor($shipId)
	{
		if (ShipResource::isAShipFromDock1($shipId)) {
			$building = OrbitalBaseResource::DOCK1;
			$size = 40;
			++$shipId;
		} elseif (ShipResource::isAShipFromDock2($shipId)) {
			$building = OrbitalBaseResource::DOCK2;
			$size = 20;
			$shipId -= 5;
		} else {
			$building = OrbitalBaseResource::DOCK3;
			$size = 10;
			$shipId -= 11;
		}
		for ($i = 0; $i <= $size; ++$i) {
			$relasedShip = $this->orbitalBaseHelper->getBuildingInfo($building, 'level', $i, 'releasedShip');
			if ($relasedShip == $shipId) {
				return $i;
			}
		}
	}
}
