<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Ship\CountMaxShipQueues;
use App\Modules\Athena\Domain\Service\Base\Ship\CountMaxStorableShipPoints;
use App\Modules\Athena\Domain\Service\Base\Ship\CountQueuedShipPoints;
use App\Modules\Athena\Domain\Service\Base\Ship\CountStoredShipPoints;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewDocks extends AbstractController
{
	public function __invoke(
        Request                       $request,
        Planet                        $currentBase,
        CurrentPlayerBonusRegistry    $currentPlayerBonusRegistry,
        Player                        $currentPlayer,
        CountMaxShipQueues            $countMaxShipQueues,
        CountMaxStorableShipPoints    $countMaxStorableShipPoints,
        CountQueuedShipPoints         $countQueuedShipPoints,
        CountStoredShipPoints         $countStoredShipPoints,
        ShipQueueRepositoryInterface  $shipQueueRepository,
        TechnologyRepositoryInterface $technologyRepository,
        DockType                      $dockType,
	): Response {
		if (0 === $dockType->getLevel($currentBase)) {
			return $this->redirectToRoute('base_overview');
		}

		$playerBonuses = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses;

		$dockLevel = $dockType->getLevel($currentBase);
		$buildingNumber = $dockType->getBuildingNumber();
		$dockSpeedBonus = $playerBonuses->get($dockType->getSpeedBonusId());
		$shipsRange = $dockType->getShipRange();
		$dockIdentifier = $dockType->getIdentifier();

		$shipQueues = $shipQueueRepository->getByBaseAndDockType($currentBase, $dockIdentifier);
		$nbShipQueues = count($shipQueues);
		$technology = $technologyRepository->getPlayerTechnology($currentPlayer);

		$inStorage = $countStoredShipPoints(base: $currentBase, dockType: $dockType);
		$inQueue = $countQueuedShipPoints($shipQueues);

		return $this->render('pages/athena/docks.html.twig', [
			'building_number' => $buildingNumber,
			'technology' => $technology,
			'dock_type' => $dockType,
			'dock_level' => $dockLevel,
			'ships_range' => $shipsRange,
			'total_space' => $countMaxStorableShipPoints($currentBase, $dockType),
			'ship_queues' => $shipQueues,
			'nb_ship_queues' => $nbShipQueues,
			'nb_dock_queues' => $countMaxShipQueues($currentBase, $dockType),
			'in_storage' => $inStorage,
			'in_queue' => $inQueue,
			'ship_resource_refund' => $this->getParameter('athena.building.ship_queue_resource_refund'),
			'dock_speed_bonus' => $dockSpeedBonus,
		]);
	}
}
