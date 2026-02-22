<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Modules\Athena\Application\Handler\CommercialRoute\GetCommercialRoutePrice;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Galaxy\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Cancel extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CommercialRouteRepositoryInterface $commercialRouteRepository,
		GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		GetCommercialRoutePrice $getCommercialRoutePrice,
		PlayerManager $playerManager,
		NotificationRepositoryInterface $notificationRepository,
		Planet $currentBase,
		Uuid $id,
	): Response {
		$cr = $commercialRouteRepository->getByIdAndPlanet($id, $currentBase);
		if (null === $cr) {
			throw $this->createNotFoundException('Commercial route not found');
		}
		if (!$cr->isProposed()) {
			throw new ConflictHttpException('Commercial route has already been established');
		}
		$routeCancelRefund = $this->getParameter('athena.trade.route.cancellation_refund');
		$proposerBase = $cr->originBase;
		$linkedBase = $cr->destinationBase;

		// rend 80% des crédits investis
		$commercialRoutePrice = $getCommercialRoutePrice($getDistanceBetweenPlaces($cr->originBase, $cr->destinationBase), $currentPlayer);
		$playerManager->increaseCredit($currentPlayer, round($commercialRoutePrice * $routeCancelRefund));

		// notification
		$notification = NotificationBuilder::new()
			->setTitle('Route commerciale annulée')
			->setContent(NotificationBuilder::paragraph(
				NotificationBuilder::link(
					$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
					$currentPlayer->name,
				),
				' a finalement retiré la proposition de route commerciale faite entre ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $linkedBase->id]),
					$linkedBase->name,
				),
				' et ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $proposerBase->id]),
					$proposerBase->name,
				),
			))
			->for($linkedBase->player);
		$notificationRepository->save($notification);

		// destruction de la route
		$commercialRouteRepository->remove($cr);

		$this->addFlash('success', 'Route commerciale annulée. Vous récupérez les '.$routeCancelRefund * 100 .'% du montant investi.');

		return $this->redirect($request->headers->get('referer'));
	}
}
