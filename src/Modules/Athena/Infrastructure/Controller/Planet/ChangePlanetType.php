<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Planet;

use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Event\PlanetOwnerChangeEvent;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Manager\PlaceManager;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Galaxy\Resource\PlaceResource;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// @TODO Simplify this hell
class ChangePlanetType extends AbstractController
{
	public function __construct(
		private readonly RecyclingMissionRepositoryInterface $recyclingMissionRepository,
	) {
	}

	#[Route(
		path: '/change-planet-type',
		name: 'change_planet_type',
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(
		Request $request,
		Planet $currentPlanet,
		Player $currentPlayer,
		BuildingQueueRepositoryInterface $buildingQueueRepository,
		PlanetManager $planetManager,
		PlanetHelper $planetHelper,
		PlanetRepositoryInterface $planetRepository,
		PlaceManager $placeManager,
		PlayerManager $playerManager,
		EventDispatcherInterface $eventDispatcher,
		EntityManagerInterface $entityManager,
	): Response {
		$type = intval($request->query->get('type') ?? throw new BadRequestHttpException('Missing base type'));

		if ($currentPlanet->isColony()) {
			// TODO Move to specification pattern
			if ($currentPlanet->levelGenerator < intval($this->getParameter('athena.obm.change_type_min_level'))) {
				throw new ConflictHttpException('Evolution de votre colonie impossible - niveau du générateur pas assez élevé');
			}

			if (!in_array($type, [Planet::BASE_TYPE_COMMERCIAL, Planet::BASE_TYPE_MILITARY])) {
				throw new BadRequestHttpException('Modification du type de la base orbitale impossible (seulement commercial ou militaire)');
			}
			$totalPrice = PlaceResource::get($type, 'price');
			if (!$currentPlayer->canAfford($totalPrice)) {
				throw new ConflictHttpException('Evolution de votre colonie impossible - vous n\'avez pas assez de crédits');
			}
			$currentPlanet->typeOfBase = $type;
			$playerManager->decreaseCredit($currentPlayer, $totalPrice);

			$this->addFlash('success', sprintf(
				'%s est désormais %s',
				$currentPlanet->name,
				match ($type) {
					Planet::BASE_TYPE_COMMERCIAL => 'un Centre Industriel',
					Planet::BASE_TYPE_MILITARY => 'une Base Militaire',
				}
			));
		} elseif ($currentPlanet->isCommercialBase() || $currentPlanet->isMilitaryBase()) {
			$baseMinLevelForCapital = intval($this->getParameter('athena.obm.capital_min_level'));
			if (Planet::BASE_TYPE_CAPITAL === $type) {
				if ($currentPlanet->levelGenerator < $baseMinLevelForCapital) {
					throw new ConflictHttpException('Pour transformer votre base en capitale, vous devez augmenter votre générateur jusqu\'au niveau '.$baseMinLevelForCapital.'.');
				}
				$playerBases = $planetRepository->getPlayerPlanets($currentPlayer);

				$capitalQuantity = 0;
				foreach ($playerBases as $playerBase) {
					if (Planet::BASE_TYPE_CAPITAL == $playerBase->typeOfBase) {
						++$capitalQuantity;
					}
				}
				if (0 < $capitalQuantity) {
					throw new ConflictHttpException('Vous ne pouvez pas avoir plus d\'une Capitale. Sauf si vous en conquérez à vos ennemis bien sûr.');
				}
				$totalPrice = PlaceResource::get(Planet::BASE_TYPE_CAPITAL, 'price');
				if (!$currentPlayer->canAfford($totalPrice)) {
					throw new ConflictHttpException('Modification du type de la base orbitale impossible - vous n\'avez pas assez de crédits');
				}
				$currentPlanet->typeOfBase = $type;
				$playerManager->decreaseCredit($currentPlayer, $totalPrice);

				$this->addFlash('success', $currentPlanet->name.' est désormais une capitale.');
			} elseif (($currentPlanet->isCommercialBase() && Planet::BASE_TYPE_MILITARY === $type)
				|| ($currentPlanet->isMilitaryBase() && Planet::BASE_TYPE_COMMERCIAL === $type)) {
				// commercial --> military OR military --> commercial
				$totalPrice = PlaceResource::get($type, 'price');
				if (!$currentPlayer->canAfford($totalPrice)) {
					throw new ConflictHttpException('modification du type de la base orbitale impossible - vous n\'avez pas assez de crédits');
				}
				$canChangeBaseType = true;
				if (Planet::BASE_TYPE_COMMERCIAL === $type) {
					$canChangeBaseType = $this->removeCommercialBaseAssets($currentPlanet);
				}
				if (!$canChangeBaseType) {
					throw new ConflictHttpException('modification du type de la base orbitale impossible (seulement capitale, commercial ou militaire)');
				}
				$playerManager->decreaseCredit($currentPlayer, $totalPrice);
				$currentPlanet->typeOfBase = $type;
				// delete commercial buildings
				for ($i = 0; $i < PlanetResource::BUILDING_QUANTITY; ++$i) {
					$maxLevel = $planetHelper->getBuildingInfo($i, 'maxLevel', $type);
					if ($currentPlanet->getBuildingLevel($i) > $maxLevel) {
						$currentPlanet->setBuildingLevel($i, $maxLevel);
					}
				}
				// delete buildings in queue
				// TODO warn player of that behavior if not already done
				// TODO Refund ?
				$buildingQueues = $buildingQueueRepository->getPlanetQueues($currentPlanet);
				foreach ($buildingQueues as $buildingQueue) {
					$buildingQueueRepository->remove($buildingQueue);
				}
				$entityManager->flush();
				// send the right alert
				if (Planet::BASE_TYPE_COMMERCIAL == $type) {
					$this->addFlash('success', 'Votre Base Militaire devient un Centre Commerciale. Vos bâtiments militaires superflus sont détruits.');
				} else {
					$this->addFlash('success', 'Votre Centre Industriel devient une Base Militaire. Vos bâtiments commerciaux superflus sont détruits.');
				}
			} else {
				throw new ConflictHttpException('modification du type de la base orbitale impossible - vous avez trop de flottes en mouvement pour changer votre base en Centre Industriel');
			}
		} elseif ($currentPlanet->isCapital()) {
			/*switch ($type) {
				case Planet::BASE_TYPE_COMMERCIAL:
					$orbitalBase->typeOfBase = $type;
					# casser les bâtiments en trop
					# killer la file de construction
					throw new ErrorException('Votre base orbitale devient commerciale.', ALERT_STD_SUCCESS);
					break;
				case Planet::BASE_TYPE_MILITARY:
					$orbitalBase->typeOfBase = $type;
					# casser les bâtiments en trop
					# killer la file de construction
					throw new ErrorException('Votre base orbitale devient militaire.', ALERT_STD_SUCCESS);
					break;
				default :
					throw new ErrorException('modification du type de la base orbitale impossible (seulement commercial ou militaire)', ALERT_STD_ERROR);
					break;
			}*/
			throw new ConflictHttpException('modification du type de la base orbitale impossible - c\'est déjà une capitale !');
		} else {
			throw new ConflictHttpException('modification du type de la base orbitale impossible - type invalide');
		}
		$planetRepository->save($currentPlanet);

		$eventDispatcher->dispatch(new PlanetOwnerChangeEvent($currentPlanet));

		return $this->redirectToRoute('base_overview');
	}

	private function removeCommercialBaseAssets(Planet $currentBase): bool
	{
		// delete all recycling missions and logs
		$this->recyclingMissionRepository->removePlanetMissions($currentBase);

		return true;
	}
}
