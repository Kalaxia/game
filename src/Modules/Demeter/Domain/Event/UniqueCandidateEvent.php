<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\LoggerEvent;
use App\Shared\Domain\Specification\SelectorSpecification;
use Psr\Log\LoggerInterface;

readonly class UniqueCandidateEvent implements ConversationMessageEvent, LoggerEvent, NotificationEvent
{
	public function __construct(
		public string $factionName,
		public Player $factionAccount,
		public PoliticalEvent $politicalEvent,
		public Conversation $factionConversation,
		public Player $newLeader,
	) {
	}

	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionName} has a new unique candidate who is now leader: {newLeaderName}.', [
			'factionName' => $this->factionName,
			'newLeaderName' => $this->newLeader->name,
		]);
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

	public function getNotificationRecipientsSpecification(): ?SelectorSpecification
	{
		return null;
	}
}
