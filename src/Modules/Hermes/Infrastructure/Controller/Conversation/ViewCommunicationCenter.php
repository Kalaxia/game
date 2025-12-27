<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Conversation;

use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\ConversationUserRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class ViewCommunicationCenter extends AbstractController
{
	use ClockAwareTrait;

	#[Route(
		path: '/messages/{conversationId}',
		name: 'communication_center',
		defaults: [
			'conversationId' => null,
		],
		methods: Request::METHOD_GET
	)]
	public function __invoke(
		Request                         $request,
		ConversationRepositoryInterface $conversationRepository,
		ConversationUserRepositoryInterface $conversationUserRepository,
		ConversationMessageRepositoryInterface $conversationMessageRepository,
		NotificationRepositoryInterface $notificationRepository,
		PlayerRepositoryInterface	    $playerRepository,
		Player                          $currentPlayer,
		string|null                     $conversationId = null,
	): Response {
		$mode = (ConversationUser::CS_ARCHIVED === $request->query->getInt('mode'))
			? ConversationUser::CS_ARCHIVED
			: ConversationUser::CS_DISPLAY;
		$startNewConversation = false;
		$page = $request->query->get('page', 1);
		$messagesPage = $request->query->get('messages_page', 1);
		$conversation = $playerLastViewedAt = null;
		$messages = [];
		$recipient = null;

		if (null !== $conversationId) {
			if ('new' === $conversationId) {
				$startNewConversation = true;

				if (!empty($recipientId = $request->query->get('sendto'))) {
					$recipient = $playerRepository->get($recipientId);
				}
			} else {
				if (!Uuid::isValid($conversationId)) {
					throw new BadRequestHttpException('Invalid Conversation ID');
				}

				$conversation = $conversationRepository->getOne(Uuid::fromString($conversationId));

				if (!$conversation->hasPlayer($currentPlayer)) {
					throw $this->createAccessDeniedException('You are not part of this conversation');
				}

				$messages = $conversationMessageRepository->getConversationMessages($conversation, $messagesPage);
				$playerPart = $conversation->getPlayerPart($currentPlayer);
				$playerLastViewedAt = $playerPart->lastViewedAt;

				$playerPart->lastViewedAt = $this->now();

				$conversationUserRepository->save($playerPart);
			}
		}

		return $this->render('pages/hermes/communication_center.html.twig', [
			'conversations' => $conversationRepository->getPlayerConversations($currentPlayer, $mode, $page),
			'mode' => $mode,
			'start_new_conversation' => $startNewConversation,
			'conversation' => $conversation,
			'page' => $page,
			'messages' => $messages,
			'messages_page' => $messagesPage,
			'player_last_viewed_at' => $playerLastViewedAt,
			'recipient_id' => $recipient?->id,
			'recipient_name' => $recipient?->name,
			'notifications' => $notificationRepository->getPlayerNotificationsByArchive($currentPlayer, false),
			'archived_notifications' => $notificationRepository->getPlayerNotificationsByArchive($currentPlayer, true),
		]);
	}
}
