<?php

namespace App\Modules\Athena\Helper;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
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
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	/**
	 * TODO Refactor with Specification Pattern
	 */
	public function haveRights(int $shipId, string $type, $sup, int $quantity = 1): bool|string
	{
		if (null === ($shipCategory = ShipCategory::tryFrom($shipId))) {
			throw new \ErrorException(sprintf('shipId invalide %d (entre 0 et %d) dans haveRights de ShipResource', $shipId, count(ShipCategory::cases())));
		}
		$dockType = DockType::fromShipIdentifier($shipId);

		switch ($type) {
			// assez de ressources pour construire ?
			case 'resource':
				$price = ($this->getShipCategoriesConfiguration)($shipCategory, 'resourcePrice') * $quantity;
				if (
					ColorResource::KALANKAR === $this->currentPlayerRegistry->get()->faction->identifier
					&& in_array($shipCategory, [ShipCategory::Cruiser, ShipCategory::HeavyCruiser])
				) {
					$price -= PercentageApplier::toInt($price, ColorResource::BONUS_EMPIRE_CRUISER);
				}

				return !($sup < $price);
			case 'queue':
				return $quantity < ($this->countMaxShipQueues)(
					orbitalBase: $sup,
					dockType: $dockType,
				);
				// droit de construire le vaisseau ?
				// $sup est un objet de type OrbitalBase
			case 'shipTree':
				if ($dockType === DockType::Manufacture) {
					$level = $sup->levelDock1;

					return $shipId < $this->orbitalBaseHelper->getBuildingInfo(2, 'level', $level, 'releasedShip');
				} elseif ($dockType === DockType::Shipyard) {
					$level = $sup->levelDock2;

					return ($shipId - 6) < $this->orbitalBaseHelper->getBuildingInfo(3, 'level', $level, 'releasedShip');
				} else {
					$level = $sup->levelDock3;

					return ($shipId - 12) < $this->orbitalBaseHelper->getBuildingInfo(4, 'level', $level, 'releasedShip');
				}
			// assez de pev dans le storage et dans la queue ?
			// $sup est un objet de type OrbitalBase
			case 'pev':
				$wanted = ($this->getShipCategoriesConfiguration)($shipId, 'pev') * $quantity;

				$shipQueues = $this->shipQueueRepository->getByBaseAndDockType($sup, $dockType->getIdentifier());

				return $wanted <= ($this->countHangarAvailableStorableShipPoints)($sup, $shipQueues, $dockType);
			// a la technologie nécessaire pour constuire ce vaisseau ?
			// $sup est un objet de type Technology
			case 'techno':
				if (1 == $sup->getTechnology(($this->getShipCategoriesConfiguration)($shipId, 'techno'))) {
					return true;
				}
				return 'il vous faut développer la technologie ' . $this->technologyHelper->getInfo(($this->getShipCategoriesConfiguration)($shipId, 'techno'), 'name');
			default:
				throw new \ErrorException('type invalide dans haveRights de ShipResource');
		}
	}

	public function dockLevelNeededFor($shipId)
	{
		$dockType = DockType::fromShipIdentifier($shipId);

		if ($dockType === DockType::Manufacture) {
			$building = OrbitalBaseResource::DOCK1;
			$size = 40;
			++$shipId;
		} elseif ($dockType === DockType::Shipyard) {
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
