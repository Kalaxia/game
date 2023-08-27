<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Library\Game;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Application\Handler\GetTravelTime;
use App\Modules\Gaia\Domain\Model\TravelType;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Move extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		GetTravelTime $getTravelTime,
		GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		PlaceRepositoryInterface $placeRepository,
		Uuid $id,
	): Response {
		$commander = $commanderRepository->get($id) ?? throw $this->createNotFoundException('Commander not found');

		// TODO Voter
		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('This commander does not belong to you');
		}

		$placeId = $request->query->get('placeId') ?? throw new BadRequestHttpException('Missing place ID');

		if (!Uuid::isValid($placeId)) {
			throw new BadRequestHttpException('Invalid place ID');
		}
		$place = $placeRepository->get(Uuid::fromString($placeId)) ?? throw $this->createNotFoundException('Place not found');

		if ($commander->player->faction->id !== $place?->player?->faction->id) {
			throw new ConflictHttpException('Vous ne pouvez pas envoyer une flotte sur une planète qui ne vous appartient pas.');
		}
		$home = $commander->base;

		// TODO refactor into service
		$length = $getDistanceBetweenPlaces($home->place, $place);
		$duration = $getTravelTime($home->place, $place, TravelType::Fleet, $currentPlayerBonusRegistry->getPlayerBonus());

		if (!$commander->isAffected()) {
			throw new ConflictHttpException('Cet officier est déjà en déplacement.');
		}
		$sector = $place->system->sector;
		// TODO add an interface for faction assets entities to create a shortcut method to check the owning faction
		$isFactionSector = $sector->faction?->id === $commander->player->faction->id;

		if ($length > Commander::DISTANCEMAX && !$isFactionSector) {
			throw new ConflictHttpException('Cet emplacement est trop éloigné.');
		}
		$commanderManager->move($commander, $place, $home->place, Commander::MOVE, $duration);

		$commanderRepository->save($commander);

		return $this->redirect($request->headers->get('referer'));
	}
}
