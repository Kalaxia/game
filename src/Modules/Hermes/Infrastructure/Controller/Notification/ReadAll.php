<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReadAll extends AbstractController
{
	#[Route(
		path: '/notifications/read-all',
		name: 'read_all_notifications',
		methods: Request::METHOD_GET,
	)]
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NotificationRepositoryInterface $notificationRepository,
		EntityManagerInterface $entityManager,
	): Response {
		$notifications = $notificationRepository->getUnreadNotifications($currentPlayer);
		$nbNotifications = count($notifications);

		foreach ($notifications as $notification) {
			$notification->read = true;
		}

		$entityManager->flush();

		if ($nbNotifications > 1) {
			$this->addFlash('success', $nbNotifications.' notifications ont été marquées comme lues.');
		} elseif (1 == $nbNotifications) {
			$this->addFlash('success', 'Une notification a été marquée comme lue.');
		} else {
			$this->addFlash('success', 'Toutes vos notifications ont déjà été lues.');
		}

		return ($request->isXmlHttpRequest())
			? new Response('', 204)
			: $this->redirect($request->headers->get('referer'));
	}
}
