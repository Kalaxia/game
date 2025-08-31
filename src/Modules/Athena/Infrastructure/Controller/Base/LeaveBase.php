<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\GetCoolDownBeforeLeavingBase;
use App\Modules\Athena\Domain\Specification\CanLeaveOrbitalBase;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Manager\PlaceManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LeaveBase extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		BuildingQueueRepositoryInterface $buildingQueueRepository,
		GetCoolDownBeforeLeavingBase $getCoolDownBeforeLeavingBase,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		PlaceManager $placeManager,
		EntityManagerInterface $entityManager,
		EventDispatcherInterface $eventDispatcher,
	): Response {
		if (1 === $currentPlayerBasesRegistry->count()) {
			throw new ConflictHttpException('vous ne pouvez pas abandonner votre unique planète');
		}
		// @TODO replace with a count query
		$baseCommanders = $commanderRepository->getBaseCommanders($currentBase);

		$isAFleetMoving = \array_reduce(
			$baseCommanders,
			fn (bool $carry, Commander $commander) => $carry || $commander->isMoving(),
			false
		);
		if ($isAFleetMoving) {
			throw new ConflictHttpException('toutes les flottes de cette base doivent être immobiles');
		}

		$coolDownInHours = $getCoolDownBeforeLeavingBase();
		$canLeaveBase = new CanLeaveOrbitalBase($coolDownInHours);
		if (!$canLeaveBase->isSatisfiedBy($currentBase)) {
			throw new ConflictHttpException('Vous ne pouvez pas abandonner de base dans les ' . $coolDownInHours . ' premières relèves.');
		}

		// delete buildings in queue
		// @TODO Apply refund rules for cancelled buildings
		$buildingQueues = $buildingQueueRepository->getBaseQueues($currentBase);
		foreach ($buildingQueues as $buildingQueue) {
			$buildingQueueRepository->remove($buildingQueue);
		}

		// change base type if it is a capital
		if ($currentBase->isCapital()) {
			$newType = (0 === random_int(0, 1)) ? OrbitalBase::TYP_COMMERCIAL : OrbitalBase::TYP_MILITARY;
			// delete extra buildings
			for ($i = 0; $i < OrbitalBaseResource::BUILDING_QUANTITY; ++$i) {
				$maxLevel = $orbitalBaseHelper->getBuildingInfo($i, 'maxLevel', $newType);
				if ($currentBase->getBuildingLevel($i) > $maxLevel) {
					$currentBase->setBuildingLevel($i, $maxLevel);
				}
			}
			// change base type
			$currentBase->typeOfBase = $newType;
		}
		$place = $currentBase->place;

		$orbitalBaseManager->changeOwner($currentBase, null);
		$entityManager->flush();

		$eventDispatcher->dispatch(new PlaceOwnerChangeEvent($place));

		$this->addFlash('success', 'Base abandonnée');

		return $this->redirectToRoute('switchbase', [
			'baseId' => $currentPlayerBasesRegistry->next()->id->toRfc4122(),
		]);
	}
}
