<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;

readonly class UniqueCandidateEvent implements ConversationMessageEvent, NotificationEvent
{
	public function __construct(
		public string $factionName,
		public Player $factionAccount,
		public Conversation $factionConversation,
		public Player $newLeader,
	) {
	}

	public function getConversation(): Conversation
	{
		return $this->factionConversation;
	}

	public function getConversationMessageContent(): string
	{
		return sprintf(
			'La période de candidature est terminée. Personne d\'autre que %s n\'a présenté sa candidature.
						%s est donc maintenant désigné pour faire valoir la force de %s à travers la galaxie.
						Gloire à <strong>%s</strong>',
			$this->newLeader->name,
			$this->newLeader->name,
			$this->factionName,
			$this->newLeader->name,
		);
	}

	public function getConversationMessageAuthor(): Player
	{
		return $this->newLeader;
	}

	public function getNotificationBuilder(): NotificationBuilder
	{
		return NotificationBuilder::new()
			->setTitle('Vous avez remporté l\'élection par défaut')
			->setContent('Personne n\'a présenté de candidature contre vous, vous êtes donc automatiquement élu.');
	}

	public function getNotificationRecipients(): array
	{
		return [$this->newLeader];
	}

	public function getNotificationRecipientsSpecification(): SelectorSpecification|null
	{
		return null;
	}
}
