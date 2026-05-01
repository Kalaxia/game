<?php

declare(ticks=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Model\Election\Mandate;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Infrastructure\Validator\IsFromFaction;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\LoggerEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MandateStartEvent implements LoggerEvent, ConversationMessageEvent, NotificationEvent
{
	public function __construct(
		public Conversation $factionConversation,
		public Player $factionPlayer,
		public Mandate $expiredMandate,
		public Mandate $newMandate,
	) {
	}

	public function log(LoggerInterface $logger): void
	{
		$logger->debug('Current mandate has reached expiration. The next mandate begins now', [
			'new_leader' => $this->newMandate->leader?->name,
			'expired__mandate_id' => $this->expiredMandate->id->toRfc4122(),
			'expired_mandate_expires_at' => $this->expiredMandate->expiredAt->format('c'),
			'new_mandate_id' => $this->newMandate->id->toRfc4122(),
			'new_mandate_expires_at' => $this->newMandate->expiredAt->format('c'),
		]);
	}

	public function getConversation(): Conversation
	{
		return $this->factionConversation;
	}

	public function getConversationMessageAuthor(): Player
	{
		return $this->factionPlayer;
	}

	public function getConversationMessageContent(TranslatorInterface $translator): string
	{
		$text = "Le précédent mandat vient de s'achever, ";
		$parameters = [];

		if ($this->newMandate->leader !== null) {
			$text .= "laissant le siège du pouvoir à notre %s %s: %s";
		} else {
			$text .= "laissant le siège du pouvoir vacant jusqu'aux prochaines élections.";
		}

		return sprintf($text, ...$parameters);
	}

	public function getNotificationBuilders(): \Generator
	{
		$previousLeader = $this->expiredMandate->leader;
		$newLeader = $this->newMandate->leader;

		yield NotificationBuilder::new()
			->setTitle(
				$newLeader !== null
				? 'Un nouveau mandat démarre'
				: 'Le siège du pouvoir demeure vacant'
			)
			->setContent(
				$newLeader !== null
				? "C'est maintenant à %s que reviennent les rênes du pouvoir"
				: 'Il faudra attendre les prochaines élections pour connaître la personne qui règnera sur notre faction.'
			)
			->withRecipientSpecification(new IsFromFaction($this->newMandate->faction));

		if ($previousLeader !== null && $previousLeader->id !== $newLeader?->id) {
			yield NotificationBuilder::new()
				->setTitle('Votre mandat touche à son terme')
				->setContent('Vous avez fait votre temps à la tête de votre faction.')
				->forPlayer($previousLeader);
		}

		if ($newLeader === null) {
			return;
		}

		if ($previousLeader?->id === $newLeader->id) {
			yield NotificationBuilder::new()
				->setTitle('Un nouveau mandat débute')
				->setContent('Vous voici à nouveau à la tête de votre faction.')
				->forPlayer($newLeader);

			return;
		}

		yield NotificationBuilder::new()
			->setTitle('Votre mandat débute')
			->setContent('Les rênes du pouvoir sont désormais vôtres.')
			->forPlayer($newLeader);
	}
}
