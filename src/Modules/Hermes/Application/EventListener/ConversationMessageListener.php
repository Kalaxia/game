<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Application\EventListener;

use App\Modules\Demeter\Domain\Event\NewLeaderEvent;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(NewLeaderEvent::class, priority: -100)]
readonly class ConversationMessageListener
{
	public function __construct(
		private ConversationMessageRepositoryInterface $conversationMessageRepository,
	) {
	}

	public function __invoke(ConversationMessageEvent $event): void
	{
		$conversation = $event->getConversation();
		$message = new ConversationMessage(
			id: Uuid::v4(),
			conversation: $conversation,
			player: $event->getConversationMessageAuthor(),
			content: $event->getConversationMessageContent(),
		);

		// TODO Move all this logic to a dedicated service
		$conversation->lastMessageAt = new \DateTimeImmutable();
		$conversation->messagesCount++;


		// désarchiver tous les users
		$users = $conversation->players;
		foreach ($users as $user) {
			$user->conversationStatus = ConversationUser::CS_DISPLAY;
		}

		$this->conversationMessageRepository->save($message);
	}
}
