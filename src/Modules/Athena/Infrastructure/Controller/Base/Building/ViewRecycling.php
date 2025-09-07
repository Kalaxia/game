<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Domain\Repository\RecyclingLogRepositoryInterface;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Domain\Service\Recycling\GetMissionTime;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\CalculateTravelTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewRecycling extends AbstractController
{
	public function __construct(
		private readonly CalculateTravelTime $calculateTravelTime,
		private readonly GetMissionTime $getMissionTime,
	) {
	}

	public function __invoke(
        Planet                              $currentPlanet,
        PlanetHelper                        $planetHelper,
        RecyclingMissionRepositoryInterface $recyclingMissionRepository,
        RecyclingLogRepositoryInterface     $recyclingLogRepository,
	): Response {
		if (0 === $currentPlanet->levelRecycling) {
			return $this->redirectToRoute('base_overview');
		}

		// load recycling missions
		$planetMissions = $recyclingMissionRepository->getPlanetActiveMissions($currentPlanet);
		$missionsLogs = $recyclingLogRepository->getBaseActiveMissionsLogs($currentPlanet);
		$missionQuantity = count($planetMissions);

		$totalRecyclers = $planetHelper->getBuildingInfo(
			PlanetResource::RECYCLING,
			'level',
			$currentPlanet->levelRecycling,
			'nbRecyclers'
		);
		$busyRecyclers = 0;

		foreach ($planetMissions as $mission) {
			$busyRecyclers += $mission->recyclerQuantity;
			$busyRecyclers += $mission->addToNextMission;
		}

		$freeRecyclers = $totalRecyclers - $busyRecyclers;

		return $this->render('pages/athena/base/building/recycling.html.twig', [
			'base_missions' => array_map(fn (RecyclingMission $rm) => $this->getData($rm), $planetMissions),
			'mission_logs' => $missionsLogs,
			'mission_quantity' => $missionQuantity,
			'free_recyclers' => $freeRecyclers,
			'busy_recyclers' => $busyRecyclers,
			'total_recyclers' => $totalRecyclers,
		]);
	}

	private function getData(RecyclingMission $mission): array
	{
		// usefull vars
		$missionID = md5($mission->id.$mission->recyclerQuantity);
		$missionID = strtoupper(substr($missionID, 0, 3).'-'.substr($missionID, 3, 6).'-'.substr($missionID, 10, 2));

		// @TODO Infamous patch
		$percent = Utils::interval(Utils::now(), date('Y-m-d H:i:s', strtotime($mission->endedAt->format('c')) - $mission->cycleTime), 's') / $mission->cycleTime * 100;
		$travelTime = ($this->calculateTravelTime)($mission->base, $mission->target, TravelType::RecyclingShips, $mission->base->player);
		$beginRECY = Format::percent($travelTime, $mission->cycleTime);
		$recyclingTime = ($this->getMissionTime)($mission->base, $mission->target, $mission->base->player) - ($travelTime * 2);
		$endRECY = Format::percent($travelTime + $recyclingTime, $mission->cycleTime);

		return [
			'mission' => $mission,
			'mission_id' => $missionID,
			'percent' => $percent,
			'travel_time' => $travelTime,
			'begin_recv' => $beginRECY,
			'end_recv' => $endRECY,
			'coords' => Game::formatCoord(
				$mission->target->system->xPosition,
				$mission->target->system->yPosition,
				$mission->target->position,
				$mission->target->system->sector->identifier
			),
		];
	}
}
