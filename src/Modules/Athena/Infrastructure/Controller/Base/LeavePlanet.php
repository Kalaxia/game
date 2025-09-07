<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\GetCoolDownBeforeLeavingPlanet;
use App\Modules\Athena\Domain\Specification\CanLeavePlanet;
use App\Modules\Galaxy\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Event\PlanetOwnerChangeEvent;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Manager\PlaceManager;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Galaxy\Resource\PlanetResource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LeavePlanet extends AbstractController
{
	public function __invoke(
		Request                          $request,
		Planet                           $currentPlanet,
		CurrentPlayerPlanetsRegistry     $currentPlayerPlanetsRegistry,
		BuildingQueueRepositoryInterface $buildingQueueRepository,
		GetCoolDownBeforeLeavingPlanet   $getCoolDownBeforeLeavingBase,
		CommanderManager                 $commanderManager,
		CommanderRepositoryInterface     $commanderRepository,
		PlanetManager                    $planetManager,
		PlanetHelper                     $planetHelper,
		PlaceManager                     $placeManager,
		EntityManagerInterface           $entityManager,
		EventDispatcherInterface         $eventDispatcher,
	): Response {
		if (1 === $currentPlayerPlanetsRegistry->count()) {
			throw new ConflictHttpException('vous ne pouvez pas abandonner votre unique planète');
		}
		// @TODO replace with a count query
		$baseCommanders = $commanderRepository->getPlanetCommanders($currentPlanet);

		$isAFleetMoving = \array_reduce(
			$baseCommanders,
			fn (bool $carry, Commander $commander) => $carry || $commander->isMoving(),
			false
		);
		if ($isAFleetMoving) {
			throw new ConflictHttpException('toutes les flottes de cette base doivent être immobiles');
		}

		$coolDownInHours = $getCoolDownBeforeLeavingBase();
		$canLeavePlanet = new CanLeavePlanet($coolDownInHours);
		if (!$canLeavePlanet->isSatisfiedBy($currentPlanet)) {
			throw new ConflictHttpException('Vous ne pouvez pas abandonner de base dans les ' . $coolDownInHours . ' premières relèves.');
		}

		// delete buildings in queue
		// @TODO Apply refund rules for cancelled buildings
		$buildingQueues = $buildingQueueRepository->getPlanetQueues($currentPlanet);
		foreach ($buildingQueues as $buildingQueue) {
			$buildingQueueRepository->remove($buildingQueue);
		}

		// change base type if it is a capital
		if ($currentPlanet->isCapital()) {
			$newType = (0 === random_int(0, 1)) ? Planet::BASE_TYPE_COMMERCIAL : Planet::BASE_TYPE_MILITARY;
			// delete extra buildings
			for ($i = 0; $i < PlanetResource::BUILDING_QUANTITY; ++$i) {
				$maxLevel = $planetHelper->getBuildingInfo($i, 'maxLevel', $newType);
				if ($currentPlanet->getBuildingLevel($i) > $maxLevel) {
					$currentPlanet->setBuildingLevel($i, $maxLevel);
				}
			}
			// change base type
			$currentPlanet->typeOfBase = $newType;
		}
		$planetManager->changeOwner($currentPlanet, null);
		$entityManager->flush();

		$eventDispatcher->dispatch(new PlanetOwnerChangeEvent($currentPlanet));

		$this->addFlash('success', 'Base abandonnée');

		return $this->redirectToRoute('switchplanet', [
			'planetId' => $currentPlayerPlanetsRegistry->next()->id->toRfc4122(),
		]);
	}
}
