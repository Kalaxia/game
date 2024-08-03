<?php

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Classes\Library\Utils;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class CancelQueue extends AbstractController
{
	public function __invoke(
		Request $request,
		DurationHandler $durationHandler,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		OrbitalBaseManager $orbitalBaseManager,
		ShipQueueManager $shipQueueManager,
		ShipQueueRepositoryInterface $shipQueueRepository,
		Uuid $id,
	): Response {
		$dock = $request->query->get('dock') ?? throw new BadRequestHttpException('Missing dock parameter');

		if (!in_array($dock, [1, 2])) {
			throw new BadRequestHttpException(sprintf('Invalid shipyard %s', $dock));
		}
		$shipQueues = $shipQueueRepository->getByBaseAndDockType($currentBase, $dock);
		$nbShipQueues = count($shipQueues);

		$index = null;
		for ($i = 0; $i < $nbShipQueues; ++$i) {
			$shipQueue = $shipQueues[$i];
			// get the index of the queue
			if ($shipQueue->id->equals($id)) {
				$index = $i;
				$startDate = $shipQueue->getStartDate();
				$shipNumber = $shipQueue->shipNumber;
				$dockType = $shipQueue->dockType;
				$quantity = $shipQueue->quantity;
				break;
			}
		}

		// if it's the first, the next must restart by now
		if (0 == $index) {
			$startDate = new \DateTimeImmutable();
		}

		if (null !== $index) {
			// shift
			for ($i = $index + 1; $i < $nbShipQueues; ++$i) {
				$shipQueue = $shipQueues[$i];

				$shipQueue->endedAt = $durationHandler->getDurationEnd(
					$startDate,
					$durationHandler->getDiff($shipQueue->startedAt, $shipQueue->endedAt),
				);
				$shipQueue->startedAt = $startDate;

				// @TODO handle rescheduling
				// $scheduler->reschedule($shipQueue, $shipQueue->dEnd, $oldDate);

				$startDate = $shipQueue->getEndDate();
			}

			// @TODO handle cancellation
			// $scheduler->cancel($shipQueues[$index], $shipQueues[$index]->dEnd);
			$shipQueueRepository->remove($shipQueues[$index]);
			// give a part of the resources back
			$resourcePrice = ShipResource::getInfo($shipNumber, 'resourcePrice');
			if (1 == $dockType) {
				$resourcePrice *= $quantity;
			}
			$shipResourceRefund = $this->getParameter('athena.building.ship_queue_resource_refund');
			$resourcePrice *= $shipResourceRefund;

			$orbitalBaseManager->increaseResources($currentBase, $resourcePrice, true);

			$this->addFlash('success', 'Commande annulée, vous récupérez le '.$shipResourceRefund * 100 .'% du montant investi pour la construction');

			return $this->redirect($request->headers->get('referer'));
		} else {
			throw new ErrorException('suppression de vaisseau impossible');
		}
	}
}
