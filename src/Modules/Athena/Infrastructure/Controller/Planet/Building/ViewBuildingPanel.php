<?php

namespace App\Modules\Athena\Infrastructure\Controller\Planet\Building;

use App\Classes\Library\Chronos;
use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Resource\PlanetResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewBuildingPanel extends AbstractController
{
	#[Route(
		path: '/buildings/{identifier}/panel',
		name: 'view_building_panel',
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(
		Planet $currentPlanet,
		BuildingLevelHandler $buildingLevelHandler,
		PlanetHelper $planetHelper,
		int $identifier,
	): Response {
		if (!$planetHelper->isABuilding($identifier)) {
			throw $this->createNotFoundException('This building does not exist');
		}

		$currentLevel = $buildingLevelHandler->getBuildingLevel($currentPlanet, $identifier);
		$max = $planetHelper->getBuildingInfo($identifier, 'maxLevel', Planet::BASE_TYPE_CAPITAL);

		$noteQuantity = 0;
		$footnoteArray = [];
		$data = [];
		for ($i = 0; $i < $max; ++$i) {
			$level = $i + 1;
			// generate the exponents for the footnotes
			$alreadyANote = false;
			$note = '';
			for ($j = 0; $j < 4; ++$j) {
				if ($i == $planetHelper->getInfo($identifier, 'maxLevel', $j) - 1) {
					if (!$alreadyANote) {
						$alreadyANote = true;
						++$noteQuantity;
						$note .= '<sup>'.$noteQuantity.'</sup>';
					}
					$footnoteArray[$j] = $noteQuantity;
				}
			}
			$data[$i] = [
				'note' => $level.$note,
				'resourcePrice' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'resourcePrice')),
				'time' => Chronos::secondToFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'time'), 'lite'),
				'stats' => match ($identifier) {
					PlanetResource::GENERATOR => [
						['stat' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'nbQueues'))],
					],
					PlanetResource::REFINERY => [
						[
							'stat' => Format::numberFormat(Game::resourceProduction($planetHelper->getBuildingInfo($identifier, 'level', $level, 'refiningCoefficient'), 50)),
							'image' => 'resource',
							'alt' => 'resources',
						],
					],
					PlanetResource::STORAGE => [
						[
							'stat' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'storageSpace')),
							'image' => 'resource',
							'alt' => 'resources',
						],
					],
					PlanetResource::DOCK1, PlanetResource::DOCK2 => [
						[
							'stat' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'nbQueues')),
						],
						[
							'stat' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'storageSpace')),
							'image' => 'pev',
							'alt' => 'pev',
						],
					],
					PlanetResource::TECHNOSPHERE => [
						[
							'stat' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'nbQueues')),
						],
					],
					PlanetResource::COMMERCIAL_PLATEFORME => [
						[
							'stat' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'nbCommercialShip')),
						],
					],
					PlanetResource::RECYCLING => [
						[
							'stat' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'nbRecyclers')),
						],
					],
					PlanetResource::SPATIOPORT => [
						[
							'stat' => Format::numberFormat($planetHelper->getBuildingInfo($identifier, 'level', $level, 'nbRoutesMax')),
						],
					],
					default => throw new \LogicException(),
				},
				'points' => $planetHelper->getBuildingInfo($identifier, 'level', $level, 'points'),
			];
		}

		return $this->render('blocks/athena/building_panel.html.twig', [
			'footnote_array' => $footnoteArray,
			'quantity_array' => array_count_values($footnoteArray),
			'data' => $data,
			'current_level' => $currentLevel,
			'max_level' => $max,
			'building_number' => $identifier,
		]);
	}
}
