<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Delete extends AbstractController
{
	public function __invoke(
		Request                            $request,
		Player                             $currentPlayer,
		Planet                             $currentBase,
		CommercialRouteManager             $commercialRouteManager,
		CommercialRouteRepositoryInterface $commercialRouteRepository,
		NotificationManager                $notificationManager,
		NotificationRepositoryInterface    $notificationRepository,
		int                                $id,
	): Response {
		$cr = $commercialRouteRepository->get($id)
			?? throw $this->createNotFoundException('Commercial route not found');

		if ($cr->isProposed()) {
			throw new ConflictHttpException('Commercial route has not been established yet');
		}

		if ($cr->originBase->player->id !== $currentPlayer->id && $cr->destinationBase->player->id === $currentPlayer->id) {
			throw $this->createAccessDeniedException('This Commercial route does not belong to you');
		}
		$proposerBase = $cr->originBase;
		$linkedBase = $cr->destinationBase;

		if ($cr->originBase->id->equals($currentBase->id)) {
			$notifReceiver = $linkedBase->player;
			$myBaseName = $proposerBase->name;
			$otherBaseName = $linkedBase->name;
			$myBaseId = $proposerBase->id;
			$otherBaseId = $linkedBase->id;
		} elseif ($cr->destinationBase->id->equals($currentBase->id)) {
			$notifReceiver = $proposerBase->player;
			$myBaseName = $linkedBase->name;
			$otherBaseName = $proposerBase->name;
			$myBaseId = $linkedBase->id;
			$otherBaseId = $proposerBase->id;
		} else {
			throw new ConflictHttpException('Commercial route does not belong to the current base');
		}

		// perte du prestige pour les joueurs Négoriens
		// @TODO check if this code is used somewhere or not
		//				$S_PAM1 = $playerManager->getCurrentSession();
		//				$playerManager->newSession();
		//				$playerManager->load(array('id' => array($cr->playerId1, $cr->playerId2)));
		//				$exp = round($cr->getIncome() * $routeExperienceCoeff);
//
		//				$playerManager->changeSession($S_PAM1);

		$n = NotificationBuilder::new()
			->setTitle('Route commerciale détruite')
			->setContent(
				NotificationBuilder::paragraph(
					NotificationBuilder::link($this->generateUrl('embassy', ['player' => $currentPlayer->id]), $currentPlayer->name),
					' annule les accords commerciaux entre ',
					NotificationBuilder::link($this->generateUrl('map', ['place' => $myBaseId]), $myBaseName),
					' et ',
					NotificationBuilder::link($this->generateUrl('map', ['place' => $otherBaseId]), $otherBaseName),
					'.',
					NotificationBuilder::divider(),
					'La route commerciale qui liait les deux bases orbitales est détruite, elle ne vous rapporte donc plus rien !',
				)
			)
			->for($notifReceiver);

		$notificationRepository->save($n);

		// destruction de la route
		$commercialRouteRepository->remove($cr);

		$this->addFlash('success', 'Route commerciale détruite');

		return $this->redirect($request->headers->get('referer'));
	}
}
