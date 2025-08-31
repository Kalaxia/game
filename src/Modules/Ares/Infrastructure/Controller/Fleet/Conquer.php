<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Modules\Ares\Application\Handler\Movement\MoveFleet;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Infrastructure\Validator\Commander\CanConquer;
use App\Modules\Ares\Infrastructure\Validator\DTO\Conquest;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Domain\Service\CountPlayerPlanets;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Conquer extends AbstractController
{
	public function __invoke(
		Request                       $request,
		Player                        $currentPlayer,
		Planet                        $planet,
		CountPlayerPlanets            $countPlayerBases,
		CommanderRepositoryInterface  $commanderRepository,
		GetFactionsConfiguration      $getFactionsConfiguration,
		MoveFleet                     $moveFleet,
		PlaceRepositoryInterface      $placeRepository,
		PlayerManager                 $playerManager,
		TechnologyRepositoryInterface $technologyRepository,
		EntityManagerInterface        $entityManager,
		ValidatorInterface            $validator,
		Uuid                          $id,
	): Response {
		$conquestCost = $this->getParameter('ares.coeff.conquest_cost');
		$placeId = $request->query->get('placeId') ?? throw new BadRequestHttpException('Missing place id');

		if (!Uuid::isValid($placeId)) {
			throw new BadRequestHttpException('Invalid place id');
		}

		$place = $placeRepository->get(Uuid::fromString($placeId))
			?? throw $this->createNotFoundException('Place not found');

		$commander = $commanderRepository->get($id) ?? throw $this->createNotFoundException('Commander not found');
		// TODO Voter
		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Ce commandant ne vous appartient pas');
		}

		$totalBases = $countPlayerBases($currentPlayer);
		// compute price
		$price = $totalBases * $conquestCost;

		// TODO factorize faction bonus in BonusApplier
		$factionBonus = $getFactionsConfiguration($currentPlayer->faction, 'bonus');
		// calcul du bonus
		if (in_array(ColorResource::COLOPRICEBONUS, $factionBonus)) {
			$price -= round($price * ColorResource::BONUS_CARDAN_COLO / 100);
		}

		$technologies = $technologyRepository->getPlayerTechnology($currentPlayer);
		$conquest = new Conquest(
			commander: $commander,
			attackerTechnology: $technologies,
			attackerBasesCount: $totalBases,
			targetedPlace: $place,
		);
		$violations = $validator->validate($conquest, new CanConquer($price));

		if (0 < $violations->count()) {
			throw new ValidationFailedException($conquest, $violations);
		}

		$moveFleet(
			commander: $commander,
			origin: $commander->base->place,
			destination: $place,
			mission: CommanderMission::Colo,
		);
		// debit credit
		$playerManager->decreaseCredit($currentPlayer, $price);

		$this->addFlash('success', 'Flotte envoyée.');

		$entityManager->flush();

		if ($request->query->has('redirect')) {
			return $this->redirectToRoute('map', ['place' => $request->query->get('redirect')]);
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
