<?php

namespace App\Modules\Gaia\Helper;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Promethee\Helper\TechnologyHelper;

readonly class PlanetHelper
{
	public function __construct(
		private TechnologyHelper $technologyHelper,
		private BuildingQueueRepositoryInterface $buildingQueueRepository,
		private BuildingLevelHandler $buildingLevelHandler,
	) {
	}

	public function isABuilding(int $building): bool
	{
		return \in_array($building, PlanetResource::BUILDINGS);
	}

	public function fleetQuantity(int $typeOfBase): int
	{
		return match ($typeOfBase) {
			Planet::BASE_TYPE_COLONY, Planet::BASE_TYPE_COMMERCIAL => 2,
			Planet::BASE_TYPE_MILITARY, Planet::BASE_TYPE_CAPITAL => 5,
			default => 0,
		};
	}

	// @TODO Check for the need of this method ??
	public function getInfo($buildingNumber, $info, $level = 0, $sup = 'default')
	{
		return $this->getBuildingInfo($buildingNumber, $info, $level, $sup);
	}

	// @TODO Separate building logic from orbital-ase logic in a dedicated helper (for now)
	public function getBuildingInfo($buildingNumber, $info, $level = 0, $sup = 'default')
	{
		if ($this->isABuilding($buildingNumber)) {
			if (\in_array($info, ['name', 'column', 'frenchName', 'imageLink', 'description'])) {
				return PlanetResource::$building[$buildingNumber][$info];
			} elseif ('techno' == $info) {
				if (\in_array($buildingNumber, [3, 4, 6, 8, 9])) {
					return PlanetResource::$building[$buildingNumber][$info];
				} else {
					return -1;
				}
			} elseif ('maxLevel' == $info) {
				// $level is the type of the base
				return PlanetResource::$building[$buildingNumber][$info][$level];
			} elseif ('level' == $info) {
				if ($level <= 0 or $level > count(PlanetResource::$building[$buildingNumber]['level'])) {
					return null;
				}
				if ('time' == $sup) {
					return PlanetResource::$building[$buildingNumber][$info][$level - 1][0];
				} elseif ('resourcePrice' == $sup) {
					return PlanetResource::$building[$buildingNumber][$info][$level - 1][1];
				} elseif ('points' == $sup) {
					return PlanetResource::$building[$buildingNumber][$info][$level - 1][2];
				} else {
					if ('nbQueues' == $sup) {
						if (0 == $buildingNumber or 2 == $buildingNumber or 3 == $buildingNumber or 5 == $buildingNumber) {
							return PlanetResource::$building[$buildingNumber][$info][$level - 1][3];
						}
					} elseif ('storageSpace' == $sup) {
						if (7 == $buildingNumber) {
							return PlanetResource::$building[$buildingNumber][$info][$level - 1][3];
						} elseif (2 == $buildingNumber or 3 == $buildingNumber) {
							return PlanetResource::$building[$buildingNumber][$info][$level - 1][4];
						}
					} elseif ('refiningCoefficient' == $sup and 1 == $buildingNumber) {
						return PlanetResource::$building[$buildingNumber][$info][$level - 1][3];
					} elseif ('releasedShip' == $sup and (2 == $buildingNumber or 3 == $buildingNumber)) {
						return PlanetResource::$building[$buildingNumber][$info][$level - 1][5];
					} elseif ('releasedShip' == $sup and 4 == $buildingNumber) {
						return PlanetResource::$building[$buildingNumber][$info][$level - 1][4];
					} elseif ('nbCommercialShip' == $sup and 6 == $buildingNumber) {
						return PlanetResource::$building[$buildingNumber][$info][$level - 1][3];
					} elseif ('nbRecyclers' == $sup and 8 == $buildingNumber) {
						return PlanetResource::$building[$buildingNumber][$info][$level - 1][3];
					} elseif ('nbRoutesMax' == $sup and 9 == $buildingNumber) {
						return PlanetResource::$building[$buildingNumber][$info][$level - 1][3];
					} else {
						throw new \ErrorException('4e argument invalide dans getBuildingInfo de PlanetResource');
					}
				}
			} else {
				throw new \ErrorException('2e argument invalide dans getBuildingInfo de PlanetResource');
			}
		} else {
			throw new \ErrorException('1er argument invalide (entre 0 et 7) dans getBuildingInfo de PlanetResource');
		}

		return null;
	}

	public function haveRights($buildingId, $level, $type, $sup): bool|string
	{
		if ($this->isABuilding($buildingId)) {
			switch ($type) {
				// assez de ressources pour contruire ?
				case 'resource':
					return $sup >= $this->getBuildingInfo($buildingId, 'level', $level, 'resourcePrice');
				// encore de la place dans la queue ?
				// $sup est le nombre de batiments dans la queue
				case 'queue':
					// $buildingId n'est pas utilisé
					return $sup < $this->getBuildingInfo($buildingId, 'level', $level, 'nbQueues');
				// droit de construire le batiment ?
				// $sup est un objet de type OrbitalBase
				case 'buildingTree':
					$diminution = match ($buildingId) {
						PlanetResource::GENERATOR,
						PlanetResource::STORAGE,
						PlanetResource::DOCK1,
						PlanetResource::REFINERY,
						PlanetResource::TECHNOSPHERE => 0,
						PlanetResource::DOCK2, PlanetResource::SPATIOPORT => 20,
						PlanetResource::DOCK3 => 30,
						PlanetResource::COMMERCIAL_PLATEFORME, PlanetResource::RECYCLING => 10,
						// no break
						default => throw new \LogicException('buildingId invalide (entre 0 et 9) dans haveRights de PlanetResource'),
					};
                    if (PlanetResource::GENERATOR == $buildingId) {
                        if ($level > PlanetResource::$building[$buildingId]['maxLevel'][$sup->typeOfBase]) {
                            return 'niveau maximum atteint';
                        } else {
                            return true;
                        }
                    } else {
                        $realGeneratorLevel = $this->buildingLevelHandler->getBuildingRealLevel(
                            $sup,
                            PlanetResource::GENERATOR,
                            $this->buildingQueueRepository->getPlanetQueues($sup),
                        );

                        if (1 == $level and Planet::BASE_TYPE_COLONY == $sup->typeOfBase and in_array($buildingId, [PlanetResource::SPATIOPORT, PlanetResource::DOCK2])) {
                            return 'vous devez évoluer votre colonie pour débloquer ce bâtiment';
                        }
                        if ($level > PlanetResource::$building[$buildingId]['maxLevel'][$sup->typeOfBase]) {
                            return 'niveau maximum atteint';
                        } elseif ($level > ($realGeneratorLevel - $diminution)) {
                            return 'le niveau du générateur n\'est pas assez élevé';
                        } else {
                            return true;
                        }
                    }
				// a la technologie pour construire ce bâtiment ?
				// $sup est un objet de type Technology
				case 'techno':
					if (-1 == $this->getBuildingInfo($buildingId, 'techno')) {
						return true;
					}
					if (1 == $sup->getTechnology($this->getBuildingInfo($buildingId, 'techno'))) {
						return true;
					} else {
						return 'il vous faut développer la technologie '.$this->technologyHelper->getInfo($this->getBuildingInfo($buildingId, 'techno'), 'name');
					}
				default:
					throw new \LogicException('$type invalide (entre 1 et 4) dans haveRights de PlanetResource');
			}
		} else {
			throw new \LogicException('buildingId invalide (entre 0 et 9) dans haveRights de PlanetResource');
		}
	}
}
