<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Classes\Library\Format;
use App\Modules\Athena\Application\Handler\CommercialRoute\GetCommercialRouteIncome;
use App\Modules\Athena\Application\Handler\CommercialRoute\GetCommercialRoutePrice;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Propose extends AbstractController
{
	public function __invoke(
        Request                            $request,
        Planet                             $currentBase,
        Player                             $currentPlayer,
        GetDistanceBetweenPlaces           $getDistanceBetweenPlaces,
        GetCommercialRoutePrice            $getCommercialRoutePrice,
        GetCommercialRouteIncome           $getCommercialRouteIncome,
        PlanetHelper                       $planetHelper,
        PlanetRepositoryInterface          $planetRepository,
        CommercialRouteRepositoryInterface $commercialRouteRepository,
        NotificationRepositoryInterface    $notificationRepository,
        PlayerManager                      $playerManager,
	): Response {
		if (0 === $currentBase->levelSpatioport) {
			throw $this->createAccessDeniedException('You cannot propose a trading route without a spatioport');
		}

		$baseTo = $request->query->get('destinationBase')
			?? throw new BadRequestHttpException('Missing destination base');

		if (!Uuid::isValid($baseTo)) {
			throw new BadRequestHttpException('Invalid destination base ID');
		}

		$otherBase = $planetRepository->get(Uuid::fromString($baseTo))
			?? throw $this->createNotFoundException('Destination base not found');

		$nbrMaxCommercialRoute = $planetHelper->getBuildingInfo(PlanetResource::SPATIOPORT, 'level', $currentBase->levelSpatioport, 'nbRoutesMax');

		// Check if a route already exists between these two bases
		$alreadyARoute = null !== $commercialRouteRepository->getExistingRoute($currentBase, $otherBase);

		// TODO transform into validation constraint
		if (($commercialRouteRepository->countPlanetRoutes($currentBase) >= $nbrMaxCommercialRoute) || $alreadyARoute || $otherBase->levelSpatioport === 0) {
			throw new ConflictHttpException('Impossible de proposer une route commerciale');
		}
		$player = $otherBase->player;

		$playerFaction = $currentPlayer->faction;
		$otherFaction = $player->faction;

		// TODO move to validation constraint
		if ($playerFaction->identifier !== $otherFaction->identifier && (Color::ENEMY === $playerFaction->relations[$otherFaction->identifier] || Color::ENEMY === $otherFaction->relations[$playerFaction->identifier])) {
			throw new ConflictHttpException('impossible de proposer une route commerciale à ce joueur, vos factions sont en guerre.');
		}
		// TODO move to validation constraint
		if ($currentBase->player->id === $otherBase->player->id) {
			throw new ConflictHttpException('Vous ne pouvez pas créer de route commerciale avec votre propre planète');
		}
		$distance = $getDistanceBetweenPlaces($currentBase->place, $otherBase->place);

		$price = $getCommercialRoutePrice($distance, $currentPlayer);

		if (1 == $distance) {
			$imageLink = '1-'.random_int(1, 3);
		} elseif ($distance < 26) {
			$imageLink = '2-'.random_int(1, 3);
		} elseif ($distance < 126) {
			$imageLink = '3-'.random_int(1, 3);
		} else {
			$imageLink = '4-'.random_int(1, 3);
		}

		if (!$currentPlayer->canAfford($price)) {
			throw new ConflictHttpException('impossible de proposer une route commerciale - vous n\'avez pas assez de crédits');
		}
		// création de la route
		$cr = new CommercialRoute(
			id: Uuid::v4(),
			originBase: $currentBase,
			destinationBase: $otherBase,
			imageLink: $imageLink,
			// Store the income without bonuses to avoid incorrect data in player|faction financial reports
			income: $getCommercialRouteIncome($currentBase, $otherBase),
			proposedAt: new \DateTimeImmutable(),
			acceptedAt: null,
			statement: CommercialRoute::PROPOSED,
		);
		$commercialRouteRepository->save($cr);

		// débit des crédits au joueur
		$playerManager->decreaseCredit($currentPlayer, $price);

		$notification = NotificationBuilder::new()
			->setTitle('Proposition de route commerciale')
			->setContent(NotificationBuilder::paragraph(
				NotificationBuilder::link(
					$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
					$currentPlayer->name,
				),
				' vous propose une route commerciale liant ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $currentBase->place->id]),
					$currentBase->name,
				),
				' et ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $otherBase->place->id]),
					$otherBase->name,
				),
				'.',
				NotificationBuilder::divider(),
				'Les frais de l\'opération vous coûteraient ',
				Format::numberFormat($price),
				' crédits; Les gains estimés pour cette route sont de ',
				Format::numberFormat($getCommercialRouteIncome($currentBase, $otherBase, $player)),
				' crédits par relève.',
				NotificationBuilder::divider(),
				NotificationBuilder::link(
					$this->generateUrl('switchplanet', ['planetId' => $otherBase->id, 'page' => 'spatioport']),
					'En savoir plus ?',
				),
			))
			->for($player);
		$notificationRepository->save($notification);

		$this->addFlash('success', 'Route commerciale proposée');

		return $this->redirect($request->headers->get('referer'));
	}
}
