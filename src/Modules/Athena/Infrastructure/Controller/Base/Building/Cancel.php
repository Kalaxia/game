<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\BuildingQueueManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Cancel extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		OrbitalBaseHelper $orbitalBaseHelper,
		OrbitalBaseManager $orbitalBaseManager,
		BuildingQueueManager $buildingQueueManager,
		BuildingQueueRepositoryInterface $buildingQueueRepository,
		DurationHandler $durationHandler,
		EntityManagerInterface $entityManager,
		int $identifier,
	): Response {
		if (!$orbitalBaseHelper->isABuilding($identifier)) {
			throw new BadRequestHttpException('le bâtiment indiqué n\'est pas valide');
		}
		$buildingQueues = $buildingQueueRepository->getBaseQueues($currentBase);

		$index = null;
		$dStart = null;
		$targetLevel = null;
		$nbBuildingQueues = count($buildingQueues);
		for ($i = 0; $i < $nbBuildingQueues; ++$i) {
			$queue = $buildingQueues[$i];
			// get the last element from the correct building
			if ($queue->buildingNumber === $identifier) {
				$index = $i;
				$targetLevel = $queue->targetLevel;
				$dStart = $queue->getStartDate();
			}
		}

		if (null === $dStart || null === $index || null === $targetLevel) {
			throw new ConflictHttpException('suppression de bâtiment impossible');
		}

		// if it's the first, the next must restart by now
		if (0 === $index) {
			$dStart = new \DateTimeImmutable();
		}
		// shift
		for ($i = $index + 1; $i < $nbBuildingQueues; ++$i) {
			$queue = $buildingQueues[$i];

			$queue->endedAt = $durationHandler->getDurationEnd($dStart, $durationHandler->getDurationRemainingTime($queue));
			$queue->startedAt = $dStart;

			// @TODO handle rescheduling
			// $scheduler->reschedule($queue, $queue->dEnd, $oldDate);

			$dStart = $queue->endedAt;
		}

		// @TODO handle cancellation
		// $scheduler->cancel($buildingQueues[$index], $buildingQueues[$index]->dEnd);
		$entityManager->remove($buildingQueues[$index]);
		$entityManager->flush();

		$buildingResourceRefund = $this->getParameter('athena.building.building_queue_resource_refund');
		// give the resources back
		$resourcePrice = $orbitalBaseHelper->getBuildingInfo($identifier, 'level', $targetLevel, 'resourcePrice');
		$resourcePrice = intval(round($resourcePrice * $buildingResourceRefund));
		$orbitalBaseManager->increaseResources($currentBase, $resourcePrice);
		$this->addFlash('success', 'Construction annulée, vous récupérez le '.$buildingResourceRefund * 100 .'% du montant investi pour la construction');

		return $this->redirect($request->headers->get('referer'));
	}
}
