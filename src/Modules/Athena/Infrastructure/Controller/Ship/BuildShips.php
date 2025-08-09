<?php

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Classes\Library\Format;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Athena\Application\Factory\ShipQueueFactory;
use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Ship\CountShipResourceCost;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Helper\ShipHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuildShips extends AbstractController
{
	public function __invoke(
		Request                       $request,
		Player                        $currentPlayer,
		OrbitalBase                   $currentBase,
		OrbitalBaseManager            $orbitalBaseManager,
		OrbitalBaseHelper             $orbitalBaseHelper,
		CountShipResourceCost         $countShipResourceCost,
		ShipHelper                    $shipHelper,
		ShipQueueRepositoryInterface  $shipQueueRepository,
		ShipQueueFactory              $shipQueueFactory,
		TechnologyRepositoryInterface $technologyRepository,
		TranslatorInterface $translator,
	): Response {
		$shipIdentifier = $request->query->getInt('ship')
			?? throw new BadRequestHttpException('Missing ship identifier');
		$quantity = $request->request->getInt('quantity', 1);

		if (0 === $quantity) {
			throw new BadRequestHttpException('Quantity must be higher than 0');
		}
		if (null === ($shipCategory = ShipCategory::tryFrom($shipIdentifier))) {
			throw new BadRequestHttpException('Invalid ship identifier');
		}
		$dockType = DockType::fromShipCategory($shipCategory);
		if (DockType::Shipyard === $dockType) {
			$quantity = 1;
		}
		$shipQueues = $shipQueueRepository->getByBaseAndDockType($currentBase, $dockType->getIdentifier());
		$shipQueuesCount = count($shipQueues);
		$technos = $technologyRepository->getPlayerTechnology($currentPlayer);
		// TODO Replace with Specification pattern
		if (
			!$shipHelper->haveRights($shipIdentifier, 'resource', $currentBase->resourcesStorage, $quantity)
			|| !$shipHelper->haveRights($shipIdentifier, 'queue', $currentBase, $shipQueuesCount)
			|| !$shipHelper->haveRights($shipIdentifier, 'shipTree', $currentBase)
			|| !$shipHelper->haveRights($shipIdentifier, 'pev', $currentBase, $quantity)
			|| !$shipHelper->haveRights($shipIdentifier, 'techno', $technos)
		) {
			throw new ConflictHttpException('Missing some conditions to launch the build order');
		}
		// TODO create a dedicated service for queued durations
		$startedAt = (0 === $shipQueuesCount)
			? new \DateTimeImmutable()
			: $shipQueues[$shipQueuesCount - 1]->getEndDate();

		$shipQueue = $shipQueueFactory->create(
			orbitalBase: $currentBase,
			shipIdentifier: $shipIdentifier,
			dockType: $dockType,
			quantity: $quantity,
			startedAt: $startedAt,
		);

		$resourcePrice = ($countShipResourceCost)($shipIdentifier, $quantity, $currentPlayer);
		$orbitalBaseManager->decreaseResources($currentBase, $resourcePrice);

		$session = $request->getSession();
		$session->get('playerEvent')->add($shipQueue->getEndDate(), $this->getParameter('event_base'), $currentBase->id);

		// TODO Improve translations to put this in one line
		if (1 == $quantity) {
			$this->addFlash('success', 'Construction d\'' . $translator->trans('ship_categories.' . $shipIdentifier . '.designation', ['quantity' => $quantity]) . ' commandée');
		} else {
			$this->addFlash('success', 'Construction de ' . $translator->trans('ship_categories.' . $shipIdentifier . '.designation', ['quantity' => $quantity]) . ' commandée');
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
