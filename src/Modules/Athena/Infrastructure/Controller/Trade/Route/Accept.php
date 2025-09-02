<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Classes\Library\Format;
use App\Modules\Athena\Application\Handler\CommercialRoute\GetCommercialRoutePrice;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Accept extends AbstractController
{
	public function __invoke(
        Request                            $request,
        Player                             $currentPlayer,
        Planet                             $currentBase,
        GetDistanceBetweenPlaces           $getDistanceBetweenPlaces,
        GetCommercialRoutePrice            $getCommercialRoutePrice,
        CommercialRouteRepositoryInterface $commercialRouteRepository,
        PlanetHelper                       $planetHelper,
        PlayerManager                      $playerManager,
        NotificationRepositoryInterface    $notificationRepository,
        EntityManagerInterface             $entityManager,
        Uuid                               $id,
	): Response {
		$routeExperienceCoeff = $this->getParameter('athena.trade.experience_coeff');

		if (null === ($cr = $commercialRouteRepository->getByIdAndDistantPlanet($id, $currentBase))) {
			throw $this->createNotFoundException('Commercial route not found');
		}

		if (!$cr->isProposed()) {
			throw new ConflictHttpException('Commercial route has already been established');
		}

		$proposerFaction = $cr->originBase->player->faction;
		$acceptorFaction = $cr->destinationBase->player->faction;

		if ($proposerFaction->identifier !== $acceptorFaction->identifier && (Color::ENEMY === $proposerFaction->relations[$acceptorFaction->identifier] || Color::ENEMY === $acceptorFaction->relations[$proposerFaction->identifier])) {
			throw new ConflictHttpException('You cannot accept this route: your two factions are at war');
		}
		$proposerBase = $cr->originBase;
		$acceptorBase = $cr->destinationBase;

		$nbrMaxCommercialRoute = $planetHelper->getBuildingInfo(
			PlanetResource::SPATIOPORT,
			'level',
			$acceptorBase->levelSpatioport,
			'nbRoutesMax',
		);

		if ($commercialRouteRepository->countPlanetRoutes($acceptorBase, [CommercialRoute::STANDBY, CommercialRoute::ACTIVE]) > $nbrMaxCommercialRoute) {
			throw new ConflictHttpException('You do not have any slot left for a new route');
		}
		// compute bonus if the player is from Negore
		// TODO move to BonusApplier logic
		$distance = $getDistanceBetweenPlaces($proposerBase, $acceptorBase);
		$price = $getCommercialRoutePrice($distance, $currentPlayer);
		if (ColorResource::FALKIRR === $currentPlayer->faction->identifier) {
			$bonus = PercentageApplier::toFloat($price, ColorResource::BONUS_NEGORA_ROUTE);
			$price = round($price - $bonus);
		}

		if ($currentPlayer->credit < $price) {
			throw new ConflictHttpException('You do not have enough credits');
		}
		$playerManager->decreaseCredit($currentPlayer, $price);

		// augmentation de l'expérience des deux joueurs
		$exp = round($cr->income * $routeExperienceCoeff);
		$playerManager->increaseExperience($currentPlayer, $exp);
		$playerManager->increaseExperience($proposerBase->player, $exp);

		// activation de la route
		$cr->statement = CommercialRoute::ACTIVE;
		$cr->acceptedAt = new \DateTimeImmutable();

		$notification = NotificationBuilder::new()
			->setTitle('Route commerciale acceptée')
			->setContent(
				NotificationBuilder::paragraph(
					NotificationBuilder::link(
						$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
						$currentPlayer->name,
					),
					' a accepté la route commerciale proposée entre ',
					NotificationBuilder::link(
						$this->generateUrl('map', ['place' => $acceptorBase->id]),
						$acceptorBase->name,
					),
					' et ',
					NotificationBuilder::link(
						$this->generateUrl('map', ['place' => $proposerBase->id]),
						$proposerBase->name,
					),
					'.',
					NotificationBuilder::divider(),
					'Cette route vous rapporte ',
					Format::numberFormat($cr->income),
					' crédits par relève.',
				),
				NotificationBuilder::paragraph(
					NotificationBuilder::resourceBox('xp', $exp, 'expérience gagnée'),
					NotificationBuilder::divider(),
					NotificationBuilder::link(
						$this->generateUrl('switchplanet', ['planetId' => $proposerBase->id, 'page' => 'spatioport']),
						'En savoir plus ?',
					)
				)
			)
			->for($proposerBase->player);
		$notificationRepository->save($notification);

		$entityManager->flush();

		$this->addFlash('success', 'Route commerciale acceptée, vous gagnez '.$exp.' points d\'expérience');

		return $this->redirect($request->headers->get('referer'));
	}
}
