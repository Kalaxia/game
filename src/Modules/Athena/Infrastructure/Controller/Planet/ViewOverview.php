<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Planet;

use App\Classes\Library\Game;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Galaxy\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Resource\PlaceResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewOverview extends AbstractController
{
	#[Route(
		name: 'base_overview',
		path: '/overview',
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(
        Request                      $request,
        Planet                       $currentPlanet,
        CurrentPlayerPlanetsRegistry $currentPlayerPlanetsRegistry,
        CommercialRouteManager       $commercialRouteManager,
        CommanderRepositoryInterface $commanderRepository,
	): Response {
		return $this->render('pages/athena/overview.html.twig', [
			'commercial_routes_data' => $commercialRouteManager->getBaseCommercialData($currentPlanet),
			'commanders' => $commanderRepository->getPlanetCommanders($currentPlanet, [Commander::AFFECTED, Commander::MOVING]),
			'vanguard_positions' => PlaceResource::get($currentPlanet->typeOfBase, 'l-line-position'),
			'vanguard_positions_count' => PlaceResource::get($currentPlanet->typeOfBase, 'l-line'),
			'rear_positions' => PlaceResource::get($currentPlanet->typeOfBase, 'r-line-position'),
			'rear_positions_count' => PlaceResource::get($currentPlanet->typeOfBase, 'r-line'),
			'science_coeff' => Game::getImprovementFromScientificCoef($currentPlanet->coefHistory),
			'minimal_change_level' => $this->getParameter('athena.obm.change_type_min_level'),
			'capital_change_level' => $this->getParameter('athena.obm.capital_min_level'),
			'capitals_count' => $this->getCapitalsCount($currentPlayerPlanetsRegistry->all()),
			'building_resource_refund' => $this->getParameter('athena.building.building_queue_resource_refund'),
		]);
	}

	private function getCapitalsCount(array $planets): int
	{
		return \count(\array_filter(
			$planets,
			fn (Planet $planet) => $planet->isCapital(),
		));
	}
}
