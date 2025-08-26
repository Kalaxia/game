<?php

namespace App\Modules\Gaia\Helper;

use App\Classes\Library\Utils;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Gaia\Galaxy\GalaxyConfiguration;
use App\Modules\Gaia\Model\Place;
use App\Modules\Gaia\Model\PointLocation;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Gaia\Model\System;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class GalaxyGenerator
{
	public int $nbSystem = 0;
	/** @var list<System> */
	public array $listSystem = [];

	public int $nbPlace = 0;
	public int $popTotal = 0;

	public int $nbSector = 0;
	public int $systemDeleted = 0;

	public function __construct(
		private readonly GalaxyConfiguration $galaxyConfiguration,
		private readonly EntityManagerInterface $entityManager,
		private readonly SectorRepositoryInterface $sectorRepository,
		private readonly SystemRepositoryInterface $systemRepository,
		private readonly PlaceRepositoryInterface $placeRepository,
		private readonly ColorRepositoryInterface $colorRepository,
	) {
	}

	public function generate(): void
	{
		// generation
		$this->generateSectors();
		$this->generateSystems();
		$this->associateSystemToSector();
		$this->generatePlaces();
	}

	private function generateSystems(): void
	{
		// GENERATION DES LINES
		for ($w = 0; $w < count($this->galaxyConfiguration->galaxy['lineSystemPosition']); ++$w) {
			// line point
			$xA = $this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][0][0];
			$yA = $this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][0][1];

			$xB = $this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][1][0];
			$yB = $this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][1][1];

			$l = sqrt(($xB - $xA) ** 2 + ($yB - $yA) ** 2);

			for ($i = 1; $i <= $this->galaxyConfiguration->galaxy['size']; ++$i) {
				for ($j = 1; $j <= $this->galaxyConfiguration->galaxy['size']; ++$j) {
					// current cursor position
					$xC = $j;
					$yC = $i;

					$d = $this->distToSegment($xC, $yC, $xA, $yA, $xB, $yB);

					$thickness = $this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][2];
					$intensity = $this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][3];

					if ($d < $this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][2]) {
						// $prob = rand(0, $this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][3]);
						$prob = random_int(0, 100);

						// if ($this->galaxyConfiguration->galaxy['lineSystemPosition'][$w][2] - $d > $prob) {
						if (round($intensity - ($d * $intensity / $thickness)) >= $prob) {
							$type = $this->getSystem();

							++$this->nbSystem;

							$system = new System(
								id: Uuid::v4(),
								sector: null,
								faction: null,
								xPosition: $xC,
								yPosition: $yC,
								typeOfSystem: $type,
							);

							$this->systemRepository->save($system);

							$this->listSystem[] = $system;
						}
					}
				}
			}
		}

		// GENERATION DES ANNEAUX (circleSystemPosition)
		for ($w = 0; $w < count($this->galaxyConfiguration->galaxy['circleSystemPosition']); ++$w) {
			// line point
			$xC = $this->galaxyConfiguration->galaxy['circleSystemPosition'][$w][0][0];
			$yC = $this->galaxyConfiguration->galaxy['circleSystemPosition'][$w][0][1];

			$radius = $this->galaxyConfiguration->galaxy['circleSystemPosition'][$w][1];
			$thickness = $this->galaxyConfiguration->galaxy['circleSystemPosition'][$w][2];
			$intensity = $this->galaxyConfiguration->galaxy['circleSystemPosition'][$w][3];

			for ($i = 1; $i <= $this->galaxyConfiguration->galaxy['size']; ++$i) {
				for ($j = 1; $j <= $this->galaxyConfiguration->galaxy['size']; ++$j) {
					// current cursor position
					$xPosition = $j;
					$yPosition = $i;

					// calcul de la distance entre la case et le centre
					$d = sqrt(
						abs($xC - $xPosition) ** 2 +
						abs($yC - $yPosition) ** 2
					);

					if ($d >= ($radius - $thickness) && $d <= ($radius + $thickness)) {
						$dtoseg = abs($d - $radius);
						$prob = random_int(0, 100);

						if (round($intensity - ($dtoseg * $intensity / $thickness)) >= $prob) {
							$type = $this->getSystem();

							++$this->nbSystem;
							$system = new System(
								id: Uuid::v4(),
								sector: null,
								faction: null,
								xPosition: $xPosition,
								yPosition: $yPosition,
								typeOfSystem: $type,
							);

							$this->systemRepository->save($system);

							$this->listSystem[] = $system;
						}
					}
				}
			}
		}

		// GENERATION PAR VAGUES
		if (null !== $this->galaxyConfiguration->galaxy['systemPosition']) {
			for ($i = 1; $i <= $this->galaxyConfiguration->galaxy['size']; ++$i) {
				for ($j = 1; $j <= $this->galaxyConfiguration->galaxy['size']; ++$j) {
					// current cursor position
					$xPosition = $j;
					$yPosition = $i;

					// calcul de la distance entre la case et le centre
					$d2o = sqrt(
						abs(($this->galaxyConfiguration->galaxy['size'] / 2) - $xPosition) ** 2 +
						abs(($this->galaxyConfiguration->galaxy['size'] / 2) - $yPosition) ** 2
					);

					if ($this->isPointInMap($d2o)) {
						$type = $this->getSystem();

						++$this->nbSystem;

						$system = new System(
							id: Uuid::v4(),
							sector: null,
							faction: null,
							xPosition: $xPosition,
							yPosition: $yPosition,
							typeOfSystem: $type,
						);

						$this->systemRepository->save($system);

						$this->listSystem[] = $system;
					}
				}
			}
		}
	}

	public function generatePlaces(): void
	{
		foreach ($this->listSystem as $system) {
			$sectorDanger = 0;
			foreach ($this->galaxyConfiguration->sectors as $sector) {
				if ($system->sector->identifier == $sector['id']) {
					$sectorDanger = $sector['danger'];
					break;
				}
			}

			$placesCount = $this->getNbOfPlace($system->typeOfSystem);

			for ($i = 0; $i < $placesCount; ++$i) {
				$type = $this->getTypeOfPlace($system->typeOfSystem);

				if (1 == $type) {
					$pointsRep = random_int(1, 10);
					$abilities = [
						'population' => 0,
						'history' => 0,
						'resources' => 0,
					];

					// nombre de point a distribuer
					if ($pointsRep < 2) {
						$pointsTot = random_int(90, 100);
					} elseif ($pointsRep < 10) {
						$pointsTot = 100;
					} else {
						$pointsTot = random_int(100, 120);
					}

					// brassage du tableau
					Utils::shuffle($abilities);

					// répartition
					$z = 1;
					foreach ($abilities as $l => $v) {
						if ($z < 3) {
							$max = $pointsTot - ($z * 10);
							$max = $max < 10 ? 10 : $max;

							$points = random_int(10, $max);
							$abilities[$l] = $points;
							$pointsTot -= $points;
						} else {
							$abilities[$l] = $pointsTot < 5 ? 5 : $pointsTot;
						}

						++$z;
					}

					$population = $abilities['population'] * 250 / 100;
					$history = $abilities['history'];
					$resources = $abilities['resources'];
					$stRES = 0;
				} elseif (6 == $type) {
					$population = 0;
					$history = 0;
					$resources = 0;
					$stRES = 0;
				} else {
					$population = $this->galaxyConfiguration->places[$type - 1]['credits'];
					$resources = $this->galaxyConfiguration->places[$type - 1]['resources'];
					$history = $this->galaxyConfiguration->places[$type - 1]['history'];
					$stRES = random_int(2000000, 20000000);
				}

				// TODO DANGER
				$danger = match ($sectorDanger) {
					GalaxyConfiguration::DNG_CASUAL => random_int(0, Place::DNG_CASUAL),
					GalaxyConfiguration::DNG_EASY => random_int(3, Place::DNG_EASY),
					GalaxyConfiguration::DNG_MEDIUM => random_int(6, Place::DNG_MEDIUM),
					GalaxyConfiguration::DNG_HARD => random_int(9, Place::DNG_HARD),
					GalaxyConfiguration::DNG_VERY_HARD => random_int(12, Place::DNG_VERY_HARD),
					default => 0,
				};

				++$this->nbPlace;
				$this->popTotal += intval(round($population));
				$place = new Place(
					id: Uuid::v4(),
					player: null,
					base: null,
					system: $system,
					typeOfPlace: $type,
					position: $i + 1,
					population: $population,
					coefResources: $resources,
					coefHistory: $history,
					resources: $stRES,
					danger: $danger,
					maxDanger: $danger,
					updatedAt: new \DateTimeImmutable('-259200 seconds'),
				);

				$this->placeRepository->save($place);
			}
		}
	}

	public function generateSectors(): void
	{
		foreach ($this->galaxyConfiguration->sectors as $sector) {
			++$this->nbSector;

			$prime = (null !== $sector['beginColor'])
				? 1
				: 0;

			$faction = (null !== $sector['beginColor'])
				? $this->colorRepository->getOneByIdentifier($sector['beginColor'])
					?? throw new \LogicException('Faction not found') : null;

			$sector = new Sector(
				id: Uuid::v4(),
				identifier: $sector['id'],
				faction: $faction,
				xPosition: $sector['display'][0],
				yPosition: $sector['display'][1],
				xBarycentric: $sector['barycentre'][0],
				yBarycentric: $sector['barycentre'][1],
				tax: 5,
				name: $sector['name'],
				points: $sector['points'],
				population: 0,
				lifePlanet: 0,
				prime: $prime,
			);

			$this->sectorRepository->save($sector);
		}
	}

	public function associateSystemToSector(): void
	{
		$pl = new PointLocation();
		$systemToDelete = [];
		$k = 0;

		foreach ($this->listSystem as $system) {
			foreach ($this->galaxyConfiguration->sectors as $sector) {
				$place = $pl->pointInPolygon(
					sprintf('%d, %d', $system->xPosition, $system->yPosition),
					$sector['vertices'],
				);

				if (1 === $place or 2 === $place) {
					$systemToDelete[] = $system;
					break;
				} elseif (3 === $place) {
					$system->sector = $this->sectorRepository->getOneByIdentifier($sector['id']);

					break;
				}
			}
			++$k;
		}
		// Updates the systems sectors
		$this->entityManager->flush();

		foreach ($this->listSystem as $system) {
			if (null === $system->sector) {
				$systemToDelete[] = $system->id;
			}
		}

		// suppression des systemes sur des lignes ou des angles
		for ($i = count($this->listSystem) - 1; $i >= 0; --$i) {
			if (in_array($this->listSystem[$i]->id, $systemToDelete)) {
				$this->sectorRepository->remove($this->listSystem[$i]);

				unset($this->listSystem[$i]);
			}
		}
		// Remove the systems without sector
		$this->entityManager->flush();

		$this->systemDeleted = count($systemToDelete);
	}

	protected function isPointInMap($d2o): bool
	{
		$mask = random_int(1, $this->galaxyConfiguration->galaxy['mask']);

		if ($mask >= 3) {
			return false;
		}
		$realPosition = $this->galaxyConfiguration->galaxy['diag'] - $d2o;
		$step = $this->galaxyConfiguration->galaxy['diag'] / count($this->galaxyConfiguration->galaxy['systemPosition']);
		$currentStep = floor($realPosition / $step);

		$random = random_int(0, 100);

		return $this->galaxyConfiguration->galaxy['systemPosition'][$currentStep] > $random;
	}

	protected function l2p($x1, $x2, $y1, $y2)
	{
		return ($x1 - $y1) ** 2 + ($x2 - $y2) ** 2;
	}

	protected function distToSegment($p1, $p2, $v1, $v2, $w1, $w2)
	{
		$l2 = $this->l2p($v1, $v2, $w1, $w2);

		if (0 == $l2) {
			return sqrt($this->l2p($p1, $p2, $v1, $v2));
		}

		$t = (($p1 - $v1) * ($w1 - $v1) + ($p2 - $v2) * ($w2 - $v2)) / $l2;

		if ($t < 0) {
			return sqrt($this->l2p($p1, $p2, $v1, $v2));
		}

		if ($t > 1) {
			return sqrt($this->l2p($p1, $p2, $w1, $w2));
		}

		$tx = $v1 + $t * ($w1 - $v1);
		$ty = $v2 + $t * ($w2 - $v2);

		return sqrt($this->l2p($p1, $p2, $tx, $ty));
	}

	protected function getProportion(array $params, int $value): int
	{
		$cursor = 0;
		$type = 0;
		$min = 0;
		$max = 0;

		for ($i = 0; $i < count($params); ++$i) {
			if (0 == $i) {
				$max = $params[$i];
			} elseif ($i < count($params) - 1) {
				$min = $cursor;
				$max = $cursor + $params[$i];
			} else {
				$min = $cursor;
				$max = 100;
			}

			$cursor = $max;
			++$type;

			if ($value > $min && $value <= $max) {
				return $type;
			}
		}
		throw new \LogicException('Must return a value');
	}

	protected function getSystem(): int
	{
		return $this->getProportion($this->galaxyConfiguration->galaxy['systemProportion'], random_int(1, 100));
	}

	protected function getNbOfPlace(int $systemType): int
	{
		$nbrPlaces = $this->galaxyConfiguration->systems[$systemType - 1]['nbrPlaces'];

		return random_int($nbrPlaces[0], $nbrPlaces[1]);
	}

	protected function getTypeOfPlace(int $systemType): int
	{
		return $this->getProportion(
			$this->galaxyConfiguration->systems[$systemType - 1]['placesPropotion'],
			random_int(1, 100),
		);
	}
}
