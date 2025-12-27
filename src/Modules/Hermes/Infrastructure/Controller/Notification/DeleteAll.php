<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeleteAll extends AbstractController
{
	#[Route(
		path: '/notifications/delete-all',
		name: 'delete_all_notifications',
		methods: Request::METHOD_GET,
	)]
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NotificationRepositoryInterface $notificationRepository,
	): Response {
		$nbr = $notificationRepository->removePlayerNotifications($currentPlayer);

		if ($nbr > 1) {
			$this->addFlash('success', $nbr.' notifications ont été supprimées.');
		} elseif (1 == $nbr) {
			$this->addFlash('success', 'Une notification a été supprimée.');
		} else {
			$this->addFlash('success', 'Toutes vos notifications ont déjà été supprimées.');
		}

		return ($request->isXmlHttpRequest())
			? new Response('', 204)
			: $this->redirect($request->headers->get('referer'));
	}
}
