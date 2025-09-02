<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Classes\Library\Format;
use App\Modules\Athena\Application\Handler\CommercialRoute\GetCommercialRoutePrice;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Refuse extends AbstractController
{
	public function __invoke(
        Request                            $request,
        Player                             $currentPlayer,
        Planet                             $currentBase,
        CommercialRouteRepositoryInterface $commercialRouteRepository,
        GetCommercialRoutePrice            $getCommercialRoutePrice,
        GetDistanceBetweenPlaces           $getDistanceBetweenPlaces,
        PlayerRepositoryInterface          $playerRepository,
        PlayerManager                      $playerManager,
        NotificationRepositoryInterface    $notificationRepository,
        Uuid                               $planetId,
        Uuid                               $id,
	): Response {
		$cr = $commercialRouteRepository->getByIdAndDistantPlanet($id, $currentBase)
			?? throw $this->createNotFoundException('Commercial route not found');

		if (!$cr->isProposed()) {
			throw new ConflictHttpException('Commercial route has already been established');
		}
		$proposerBase = $cr->originBase;
		$refusingBase = $cr->destinationBase;

		// rend les crédits au proposant
		$price = $getCommercialRoutePrice(
			$getDistanceBetweenPlaces($proposerBase, $refusingBase),
			$proposerBase->player,
		);
		$playerManager->increaseCredit($proposerBase->player, $price);

		// notification
		$notification = NotificationBuilder::new()
			->setTitle('Route commerciale refusée')
			->setContent(NotificationBuilder::paragraph(
				NotificationBuilder::link(
					$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
					$currentPlayer->name,
				),
				' a refusé la route commerciale proposée entre ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $refusingBase->id]),
					$refusingBase->name,
				),
				' et ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $proposerBase->id]),
					$proposerBase->name,
				),
				'.',
				NotificationBuilder::divider(),
				'Les ',
				Format::numberFormat($price),
				' crédits bloqués sont à nouveau disponibles.',
			))
			->for($proposerBase->player);

		$notificationRepository->save($notification);

		// destruction de la route
		$commercialRouteRepository->remove($cr);

		$this->addFlash('success', 'Route commerciale refusée');

		return $this->redirect($request->headers->get('referer'));
	}
}
