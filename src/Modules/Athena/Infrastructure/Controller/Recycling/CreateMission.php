<?php

namespace App\Modules\Athena\Infrastructure\Controller\Recycling;

use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Domain\Service\Recycling\GetMissionTime;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\RecyclingMissionManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class CreateMission extends AbstractController
{
	public function __invoke(
		Request                             $request,
		Player                              $currentPlayer,
		CurrentPlayerBonusRegistry          $currentPlayerBonusRegistry,
		GetMissionTime                      $getMissionTime,
		OrbitalBase                         $currentBase,
		OrbitalBaseHelper                   $orbitalBaseHelper,
		PlaceRepositoryInterface            $placeRepository,
		RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		RecyclingMissionManager $recyclingMissionManager,
		Uuid                                $targetId,
	): Response {
		$quantity = $request->request->getInt('quantity', 0);

		if ($quantity < 1) {
			throw new BadRequestHttpException('Ca va être dur de recycler avec autant peu de recycleurs. Entrez un nombre plus grand que zéro.');
		}
		$maxRecyclers = $orbitalBaseHelper->getInfo(OrbitalBaseResource::RECYCLING, 'level', $currentBase->levelRecycling, 'nbRecyclers');
		$usedRecyclers = 0;

		$baseMissions = $recyclingMissionRepository->getBaseActiveMissions($currentBase);

		foreach ($baseMissions as $mission) {
			$usedRecyclers += $mission->recyclerQuantity;
			$usedRecyclers += $mission->addToNextMission;
		}

		if ($maxRecyclers - $usedRecyclers < $quantity) {
			throw new BadRequestHttpException('Vous n\'avez pas assez de recycleurs libres pour lancer cette mission.');
		}
		$destinationPlace = $placeRepository->get($targetId)
			?? throw $this->createNotFoundException('Il y a un problème avec le lieu de départ ou d\'arrivée. Veuillez contacter un administrateur.');

		$startPlace = $currentBase->place;
		// TODO Make constants or enum
		if (null !== $destinationPlace->player || !in_array($destinationPlace->typeOfPlace, [2, 3, 4, 5])) {
			throw new BadRequestHttpException('On ne peut pas recycler ce lieu, petit hacker.');
		}

		$sectorFaction = $destinationPlace->system->sector->faction;
		if (null !== $sectorFaction && !$currentPlayer->faction->id->equals($sectorFaction->id)) {
			throw $this->createAccessDeniedException('Vous pouvez recycler uniquement dans les secteurs de votre faction ainsi que dans les secteurs neutres.');
		}
		$missionTime = $getMissionTime($startPlace, $destinationPlace, $currentPlayer);

		$rm = new RecyclingMission(
			id: Uuid::v4(),
			base: $currentBase,
			target: $destinationPlace,
			cycleTime: $missionTime,
			recyclerQuantity: $quantity,
			statement: RecyclingMission::ST_ACTIVE,
			endedAt: new \DateTimeImmutable(sprintf('+%d seconds', $missionTime)),
		);

		$recyclingMissionManager->add($rm);

		$this->addFlash('success', 'Votre mission a été lancée.');

		return $this->redirect($request->headers->get('referer'));
	}
}
