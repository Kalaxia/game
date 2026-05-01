<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Application\EventListener;

use App\Modules\Demeter\Domain\Event\MandateStartEvent;
use App\Modules\Demeter\Domain\Event\MissingCandidatesEvent;
use App\Modules\Demeter\Domain\Event\NewDemocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewRoyalisticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\PutschFailedEvent;
use App\Modules\Demeter\Domain\Event\UniqueCandidateEvent;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(PutschFailedEvent::class)]
#[AsEventListener(UniqueCandidateEvent::class)]
#[AsEventListener(MandateStartEvent::class)]
#[AsEventListener(MissingCandidatesEvent::class)]
#[AsEventListener(NewDemocraticLeaderEvent::class)]
#[AsEventListener(NewTheocraticLeaderEvent::class)]
#[AsEventListener(NewRoyalisticLeaderEvent::class)]
readonly class ConversationMessageListener
{
	public function __construct(
		private TranslatorInterface $translator,
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
			content: $event->getConversationMessageContent($this->translator),
		);

		// TODO Move all this logic to a dedicated service
		$conversation->lastMessageAt = new \DateTimeImmutable();
		++$conversation->messagesCount;

		// désarchiver tous les users
		$users = $conversation->players;
		foreach ($users as $user) {
			$user->conversationStatus = ConversationUser::CS_DISPLAY;
		}

		$this->conversationMessageRepository->save($message);
	}
}
