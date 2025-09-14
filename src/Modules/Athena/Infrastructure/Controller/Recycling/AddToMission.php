<?php

namespace App\Modules\Athena\Infrastructure\Controller\Recycling;

use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Resource\PlanetResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class AddToMission extends AbstractController
{
	public function __invoke(
        Request                             $request,
        Planet                              $currentPlanet,
        RecyclingMissionRepositoryInterface $recyclingMissionRepository,
        Uuid                                $id,
	): Response {
		$quantity = $request->request->getInt('quantity');

		if (0 === $quantity) {
			throw new BadRequestHttpException('Ca va être dur de recycler avec aussi peu de recycleurs. Entrez un nombre plus grand que zéro.');
		}
		$usedRecyclers = 0;

		$planetMissions = $recyclingMissionRepository->getPlanetActiveMissions($currentPlanet);

		$mission = null;
		foreach ($planetMissions as $planetMission) {
			$usedRecyclers += $planetMission->recyclerQuantity + $planetMission->addToNextMission;
			if ($planetMission->id->equals($id) && $planetMission->isActive()) {
				$mission = $planetMission;
			}
		}

		if (null === $mission) {
			throw $this->createNotFoundException('Il y a un problème, la mission est introuvable. Veuillez contacter un administrateur.');
		}

		$mission->addToNextMission += $quantity;

		$recyclingMissionRepository->save($mission);

		$this->addFlash('success', 'Vos recycleurs ont bien été affectés, ils seront ajoutés à la prochaine mission.');

		return $this->redirect($request->headers->get('referer'));
	}
}
