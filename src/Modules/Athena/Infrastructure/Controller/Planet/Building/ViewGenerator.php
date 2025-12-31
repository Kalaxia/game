<?php

namespace App\Modules\Athena\Infrastructure\Controller\Planet\Building;

use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewGenerator extends AbstractController
{
	#[Route(
		name: 'generator',
		path: '/generator',
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(
        BuildingQueueRepositoryInterface $buildingQueueRepository,
        CurrentPlayerBonusRegistry       $currentPlayerBonusRegistry,
        Player                           $currentPlayer,
        Planet                           $currentBase,
        TechnologyManager                $technologyManager,
        TechnologyRepositoryInterface    $technologyRepository,
	): Response {
		$technology = $technologyRepository->getPlayerTechnology($currentPlayer);
		$buildingQueues = $buildingQueueRepository->getPlanetQueues($currentBase);
		$buildingQueuesCount = count($buildingQueues);

		return $this->render('pages/athena/generator.html.twig', [
			'technology' => $technology,
			'generator_speed_bonus' => $currentPlayerBonusRegistry
				->getPlayerBonus()->bonuses->get(PlayerBonusId::GENERATOR_SPEED),
			'building_resource_refund' => $this->getParameter('athena.building.building_queue_resource_refund'),
			'building_queues' => $buildingQueues,
			'building_queues_count' => $buildingQueuesCount,
		]);
	}
}
