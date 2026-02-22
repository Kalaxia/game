<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\LoggerEvent;
use App\Shared\Domain\Specification\SelectorSpecification;

abstract class NewLeaderEvent implements LoggerEvent, NotificationEvent, ConversationMessageEvent
{
	public function __construct(
		public Color $faction,
		public Player $newLeader,
		public PoliticalEvent $politicalEvent,
		protected string $factionName,
		protected array $factionStatuses,
		protected Player $factionPlayer,
		protected Conversation $factionConversation,
		protected array $candidatesData,
	) {
	}

	public function getConversation(): Conversation
	{
		return $this->factionConversation;
	}

	public function getConversationMessageAuthor(): Player
	{
		return $this->factionPlayer;
	}

	abstract public function getConversationMessageContent(): string;

	abstract public function getNotificationBuilder(): NotificationBuilder;

	public function getNotificationRecipients(): array
	{
		return [$this->newLeader];
	}

	public function getNotificationRecipientsSpecification(): ?SelectorSpecification
	{
		return null;
	}
}
