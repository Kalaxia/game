<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Classes\Library\Game;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Domain\Service\GetTimeCost;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Promethee\Manager\TechnologyQueueManager;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ViewTechnosphere extends AbstractController
{
	public function __construct(
		private readonly ResearchRepositoryInterface $researchRepository,
		private readonly TechnologyHelper $technologyHelper,
		private readonly ResearchManager $researchManager,
		private readonly GetTimeCost $getTimeCost,
	) {
	}

	public function __invoke(
        Request                            $request,
        Planet                             $currentPlanet,
        CurrentPlayerBonusRegistry         $currentPlayerBonusRegistry,
        Player                             $currentPlayer,
        TechnologyManager                  $technologyManager,
        TechnologyQueueManager             $technologyQueueManager,
        TechnologyQueueRepositoryInterface $technologyQueueRepository,
        TechnologyRepositoryInterface      $technologyRepository,
        PlanetHelper                       $planetHelper,
	): Response {
		if ($currentPlanet->levelTechnosphere === 0) {
			return $this->redirectToRoute('base_overview');
		}

		$technologyResourceRefund = $this->getParameter('promethee.technology_queue.resource_refund');

		$technology = $technologyRepository->getPlayerTechnology($currentPlayer);

		// session avec les technos de cette base
		$baseTechnologyQueues = $technologyQueueRepository->getPlanetQueues($currentPlanet);
		$playerTechnologyQueues = $technologyQueueRepository->getPlayerQueues($currentPlayer);

		$coef = $currentPlanet->coefHistory;
		$coefBonus = Game::getImprovementFromScientificCoef($coef);
		$techBonus = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::TECHNOSPHERE_SPEED);
		$factionBonus = 0;
		/*if (ColorResource::APHERA == $currentPlayer->faction->identifier) {
			// bonus if the player is from Aphera
			$factionBonus += ColorResource::BONUS_APHERA_TECHNO;
		}*/
		$totalBonus = $coefBonus + $techBonus + $factionBonus;

		return $this->render('pages/athena/base/building/technosphere.html.twig', [
			'has_financial_technologies' => in_array($currentPlanet->typeOfBase, [Planet::BASE_TYPE_COMMERCIAL, Planet::BASE_TYPE_CAPITAL]),
			'has_military_technologies' => in_array($currentPlanet->typeOfBase, [Planet::BASE_TYPE_MILITARY, Planet::BASE_TYPE_CAPITAL]),
			'planet_queues' => $baseTechnologyQueues,
			'player_queues' => $playerTechnologyQueues,
			'available_queues' => $planetHelper->getBuildingInfo(PlanetResource::TECHNOSPHERE, 'level', $currentPlanet->levelTechnosphere, 'nbQueues'),
			'total_bonus' => $totalBonus,
			'technology_resource_refund' => $technologyResourceRefund,
			'technologies_data' => $this->getTechnologiesData(
				$currentPlayer,
				$currentPlanet,
				$technology,
				$baseTechnologyQueues,
				$playerTechnologyQueues,
				$totalBonus,
			),
		]);
	}

	/**
	 * @param list<TechnologyQueue> $baseTechnologyQueues
	 * @param list<TechnologyQueue> $playerTechnologyQueues
	 */
	private function getTechnologiesData(
        Player     $currentPlayer,
        Planet     $currentBase,
        Technology $technology,
        array      $baseTechnologyQueues,
        array      $playerTechnologyQueues,
        int        $totalBonus,
	): array {
		$data = [];

		$playerResearch = $this->researchRepository->getPlayerResearch($currentPlayer)
			?? throw new ConflictHttpException('Player must have an associated Research entity');

		foreach (TechnologyId::getAll() as $technologyId) {
			if ($this->technologyHelper->isATechnologyNotDisplayed($technologyId)) {
				continue;
			}
			$disability = 'disable';
			$closed = '';
			$inQueue = false;
			$inALocalQueue = false;
			$isAnUnblockingTechnology = $this->technologyHelper->isAnUnblockingTechnology($technologyId);

			foreach ($playerTechnologyQueues as $playerQueue) {
				if ($playerQueue->technology !== $technologyId) {
					continue;
				}
				$inQueue = true;
				foreach ($baseTechnologyQueues as $baseQueue) {
					if ($baseQueue->technology === $technologyId) {
						$inALocalQueue = true;
						break;
					}
				}
				break;
			}

			$technologyLevel = $technology->getTechnology($technologyId);
			$nextLevel = $technologyLevel + 1;
			$researchRequirements = $this->technologyHelper->haveRights(
				$technologyId,
				'research',
				($isAnUnblockingTechnology) ? 1 : $nextLevel,
				$this->researchManager->getResearchList($playerResearch),
			);

			// compute time to build with the bonuses
			$timeToBuild = $this->technologyHelper->getInfo($technologyId, 'time', $nextLevel);
			$timeToBuild -= round($timeToBuild * $totalBonus / 100);
			// warning : $totalBonus est défini plus haut (ne pas inverser les blocs de code !)
			$timeToBuild = ($this->getTimeCost)($technologyId, $nextLevel, $currentBase->coefHistory);

			$column = $this->technologyHelper->getInfo($technologyId, 'column');

			$data[$column] ??= [];
			$data[$column][] = [
				'identifier' => $technologyId,
				'is_unblocking_technology' => $isAnUnblockingTechnology,
				'technology_level' => $technologyLevel,
				'next_level' => $nextLevel,
				'max_level_requirements' => $this->technologyHelper->haveRights($technologyId, 'maxLevel', $nextLevel),
				'queue_requirements' => $this->technologyHelper->haveRights($technologyId, 'queue', $currentBase, count($baseTechnologyQueues)),
				'credit_requirements' => $this->technologyHelper->haveRights($technologyId, 'credit', $nextLevel, $currentPlayer->getCredits()),
				'resource_requirements' => $this->technologyHelper->haveRights($technologyId, 'resource', $nextLevel, $currentBase->resourcesStorage),
				'technosphere_requirements' => $this->technologyHelper->haveRights($technologyId, 'technosphereLevel', $currentBase->levelTechnosphere),
				'research_requirements' => $researchRequirements,
				'in_queue' => $inQueue,
				'in_local_queue' => $inALocalQueue,
				'is_over' => $isAnUnblockingTechnology && $technologyLevel,
				'time_to_build' => $timeToBuild,
			];
		}

		return $data;
	}
}
