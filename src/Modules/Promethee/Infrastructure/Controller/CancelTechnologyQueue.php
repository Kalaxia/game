<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Manager\TechnologyQueueManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CancelTechnologyQueue extends AbstractController
{
	public function __invoke(
		DurationHandler $durationHandler,
		Request $request,
		Player $currentPlayer,
		Planet $currentBase,
		TechnologyHelper $technologyHelper,
		TechnologyQueueManager $technologyQueueManager,
		TechnologyQueueRepositoryInterface $technologyQueueRepository,
		PlayerManager $playerManager,
		PlanetManager $planetManager,
		int $identifier,
	): Response {
		if (!$technologyHelper->isATechnology($identifier)) {
			throw new BadRequestHttpException('la technologie indiquée n\'est pas valide');
		}
		$placeTechnologyQueues = $technologyQueueRepository->getPlanetQueues($currentBase);

		$index = $startedAt = null;
		$targetLevel = 0;
		foreach ($placeTechnologyQueues as $i => $queue) {
			// get the queue to delete
			if ($queue->technology === $identifier && $queue->targetLevel > $targetLevel) {
				$index = $i;
				$targetLevel = $queue->targetLevel;
				$startedAt = $queue->getStartDate();
			}
		}

		// if it's the first, the next must restart by now
		if (0 == $index) {
			$startedAt = new \DateTimeImmutable();
		}

		if (null === $index || null === $startedAt) {
			throw new ConflictHttpException('impossible d\'annuler la technologie');
		}

		// shift
		foreach (array_slice($placeTechnologyQueues, $index + 1) as $queue) {
			// $oldDate = $queue->dEnd;
			// TODO maybe carve out this code portion
			$queue->endedAt = $durationHandler->getDurationEnd(
				$startedAt,
				$durationHandler->getDiff($queue->getStartDate(), $queue->getEndDate()),
			);
			$queue->startedAt = $startedAt;
			// @TODO handle rescheduling
			// $scheduler->reschedule($queue, $queue->dEnd, $oldDate);

			$startedAt = $queue->getEndDate();
		}

		// @TODO handle cancellation
		// $scheduler->cancel($placeTechnologyQueues[$index], $placeTechnologyQueues[$index]->getEndedAt());
		$technologyQueueRepository->remove($placeTechnologyQueues[$index]);

		$technologyResourceRefund = $this->getParameter('promethee.technology_queue.resource_refund');
		$technologyCreditRefund = $this->getParameter('promethee.technology_queue.credit_refund');
		// rends les ressources et les crédits au joueur
		$resourcePrice = $technologyHelper->getInfo($identifier, 'resource', $targetLevel);
		$resourcePrice = intval(round($resourcePrice * $technologyResourceRefund));
		$planetManager->increaseResources($currentBase, $resourcePrice);
		$creditPrice = $technologyHelper->getInfo($identifier, 'credit', $targetLevel);
		$creditPrice = intval(round($creditPrice * $technologyCreditRefund));
		$playerManager->increaseCredit($currentPlayer, $creditPrice);
		$this->addFlash('success', 'Construction annulée, vous récupérez le '.$technologyResourceRefund * 100 .'% des ressources ainsi que le '.$technologyCreditRefund * 100 .'% des crédits investis pour le développement');

		return $this->redirect($request->headers->get('referer'));
	}
}
