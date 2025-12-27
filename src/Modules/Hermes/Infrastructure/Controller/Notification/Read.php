<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class Read extends AbstractController
{
	#[Route(
		path: '/notifications/{id}/read',
		name: 'read_notification',
		methods: Request::METHOD_PATCH,
	)]
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NotificationRepositoryInterface $notificationRepository,
		Uuid $id,
	): Response {
		if (null === ($notification = $notificationRepository->get($id))) {
			throw $this->createNotFoundException('Notification not found');
		}
		// TODO replace with Voter
		if ($notification->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Cette notification ne vous appartient pas');
		}
		$notification->read = true;
		$notificationRepository->save($notification);

		return ($request->isXmlHttpRequest())
			? new Response('', 204)
			: $this->redirect($request->headers->get('referer'));
	}
}
