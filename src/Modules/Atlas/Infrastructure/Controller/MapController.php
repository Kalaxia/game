<?php

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Classes\Container\Params;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Manager\ConquestManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Artemis\Domain\Repository\SpyReportRepositoryInterface;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class MapController extends AbstractController
{
	public function __construct(
		private readonly SystemRepositoryInterface    $systemRepository,
		private readonly PlaceRepositoryInterface $placeRepository,
	) {
	}

	public function __invoke(
		Request                             $request,
		Planet                              $currentBase,
		Player                              $currentPlayer,
		ConquestManager                     $conquestManager,
		CommanderManager                    $commanderManager,
		CommanderRepositoryInterface        $commanderRepository,
		SectorRepositoryInterface           $sectorRepository,
		PlanetRepositoryInterface           $planetRepository,
		PlanetManager                       $planetManager,
		TechnologyRepositoryInterface       $technologyRepository,
		RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		GalaxyConfiguration                 $galaxyConfiguration,
		SpyReportRepositoryInterface        $spyReportRepository,
		ReportRepositoryInterface           $reportRepository,
	): Response {
		$defaultPosition = $this->getDefaultPosition($request, $currentBase);
		$selectedSystemData = [];
		$movingCommanders = $commanderRepository->getPlayerCommanders($currentPlayer, [Commander::MOVING]);

		if (null !== $defaultPosition['system']) {
			$system = $defaultPosition['system'];
			$places = $this->placeRepository->getSystemPlaces($system);
			$placesIds = array_map(fn (Place $place) => $place->id, $places);

			$basesCount = $planetManager->countPlayerPlanets($movingCommanders);

			$selectedSystemData = [
				'system' => $defaultPosition['system'],
				'places' => $places,
				'technologies' => $technologyRepository->getPlayerTechnology($currentPlayer),
				'recycling_missions' => $recyclingMissionRepository->getPlanetActiveMissions($currentBase),
				'spy_reports' => $spyReportRepository->getSystemReports($currentPlayer, $placesIds),
				'combat_reports' => $reportRepository->getAttackReportsByPlaces($currentPlayer, $placesIds),
				'colonization_cost' => $conquestManager->getColonizationCost($currentPlayer, $basesCount),
				'conquest_cost' => $conquestManager->getConquestCost($currentPlayer, $basesCount),
			];
		}

		return $this->render('pages/atlas/map.html.twig', array_merge([
			'sectors' => $sectorRepository->getAll(),
			'systems' => $this->systemRepository->getAll(),
			'player_planets' => $planetRepository->getPlayerPlanets($currentPlayer),
			'default_position' => $defaultPosition,
			'default_map_parameters' => Params::$params,
			'galaxy_configuration' => $galaxyConfiguration,
			'local_commanders' => $commanderRepository->getPlanetCommanders(
				$currentBase,
				[Commander::AFFECTED, Commander::MOVING],
				['line' => 'DESC'],
			),
			'moving_commanders' => $movingCommanders,
			'attacking_commanders' => array_merge(
				$commanderManager->getVisibleIncomingAttacks($currentPlayer),
				$commanderRepository->getOutcomingAttacks($currentPlayer)
			),
		], $selectedSystemData));
	}

	/**
	 * @return array{ x: int, y: int, system: System|null, place: Planet|null, system_id: Uuid|null }
	 */
	protected function getDefaultPosition(
        Request $request,
        Planet  $currentBase,
	): array {
		// map default position
		$x = $currentBase->system->xPosition;
		$y = $currentBase->system->yPosition;
		$systemId = null;
		$system = $place = null;

		// other default location
		// par place
		if ($request->query->has('place')) {
			if (($place = $this->placeRepository->get(Uuid::fromString($request->query->get('place')))) !== null) {
				$system = $place->system;
				$x = $system->xPosition;
				$y = $system->yPosition;
				$systemId = $system->id;
			}
			// par système
		} elseif ($request->query->has('systemid')) {
			if (($system = $this->systemRepository->get(Uuid::fromString($request->query->get('systemid')))) !== null) {
				$x = $system->xPosition;
				$y = $system->yPosition;
				$systemId = $system->id;
			}
			// par coordonnée
		} elseif ($request->query->has('x') && $request->query->has('y')) {
			$x = $request->query->get('x');
			$y = $request->query->get('y');
		}

		return [
			'x' => $x,
			'y' => $y,
			'system_id' => $systemId,
			'system' => $system,
			'place' => $place,
		];
	}
}
