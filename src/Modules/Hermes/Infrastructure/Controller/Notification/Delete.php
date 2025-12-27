<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class Delete extends AbstractController
{
	#[Route(
		path: '/notifications/{id}/delete',
		name: 'delete_notification',
		methods: Request::METHOD_GET,
	)]
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NotificationRepositoryInterface $notificationRepository,
		Uuid $id,
	): Response {
		if (null === ($notification = $notificationRepository->get($id))) {
			throw new NotFoundHttpException('Notification not found');
		}
		// TODO replace with Voter
		if ($notification->player->id !== $currentPlayer->id) {
			throw new AccessDeniedHttpException();
		}
		$notificationRepository->remove($notification);

		return ($request->isXmlHttpRequest())
			? new Response('', 204)
			: $this->redirect($request->headers->get('referer'));
	}
}
