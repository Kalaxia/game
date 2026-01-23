<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;

readonly class PutschFailedEvent implements NotificationEvent, ConversationMessageEvent
{
	public function __construct(
		public Player $putchist,
		public Player|null $leader,
		public Player $factionAccount,
		public Conversation $factionConversation,
		public string $factionName,
	) {
	}

	public function getConversation(): Conversation
	{
		return $this->factionConversation;
	}

	public function getConversationMessageContent(): string
	{
		return sprintf(
			'Un coup d\'état a échoué. %s demeure le dirigeant de %s',
			$this->leader?->name,
			$this->factionName,
		);
	}

	public function getConversationMessageAuthor(): Player
	{
		return $this->factionAccount;
	}

	public function getNotificationBuilder(): NotificationBuilder
	{
		return NotificationBuilder::new()
			->setTitle('Votre coup d\'état a échoué')
			->setContent(NotificationBuilder::paragraph(
				'Le peuple ne vous a pas soutenu, l\'ancien gouvernement reste en place.'
			));
	}

	public function getNotificationRecipients(): array
	{
		return [$this->putchist];
	}

	public function getNotificationRecipientsSpecification(): SelectorSpecification|null
	{
		return null;
	}
}
