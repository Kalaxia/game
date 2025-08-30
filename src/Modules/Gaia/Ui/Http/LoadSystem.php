<?php

namespace App\Modules\Gaia\Ui\Http;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Manager\ConquestManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Artemis\Domain\Repository\SpyReportRepositoryInterface;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Manager\SystemManager;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class LoadSystem extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		Player $currentPlayer,
		CommanderRepositoryInterface $commanderRepository,
		SystemRepositoryInterface $systemRepository,
		PlaceManager $placeManager,
		PlaceRepositoryInterface $placeRepository,
		TechnologyRepositoryInterface $technologyRepository,
		ConquestManager $conquestManager,
		SpyReportRepositoryInterface $spyReportRepository,
		ReportRepositoryInterface $reportRepository,
		OrbitalBaseManager $orbitalBaseManager,
		RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		Uuid $id
	): Response {
		$system = $systemRepository->get($id) ?? throw $this->createNotFoundException('System not found');

		$places = $placeRepository->getSystemPlaces($system);

		$movingCommanders = $commanderRepository->getPlayerCommanders($currentPlayer, [Commander::MOVING]);

		$placesIds = array_map(fn (Place $place) => $place->id, $places);

		$basesCount = $orbitalBaseManager->getPlayerBasesCount($movingCommanders);

		return $this->render('components/map/system_details.html.twig', [
			'system' => $system,
			'places' => $places,
			'moving_commanders' => $movingCommanders,
			'technologies' => $technologyRepository->getPlayerTechnology($currentPlayer),
			'recycling_missions' => $recyclingMissionRepository->getBaseActiveMissions($currentBase),
			'spy_reports' => $spyReportRepository->getSystemReports($currentPlayer, $placesIds),
			'combat_reports' => $reportRepository->getAttackReportsByPlaces($currentPlayer, $placesIds),
			'colonization_cost' => $conquestManager->getColonizationCost($currentPlayer, $basesCount),
			'conquest_cost' => $conquestManager->getConquestCost($currentPlayer, $basesCount),
		]);
	}
}
