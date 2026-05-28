<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\LoggerEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PutschFailedEvent implements LoggerEvent, NotificationEvent, ConversationMessageEvent
{
	public function __construct(
		public Player $putschist,
		public ?Player $leader,
		public Player $factionAccount,
		public Conversation $factionConversation,
	) {
	}

	public function log(LoggerInterface $logger): void
	{
		$logger->info('Putsch failed for {factionIdentifier}.', [
			'factionIdentifier' => $this->putschist->faction->identifier,
		]);
	}

	public function getConversation(): Conversation
	{
		return $this->factionConversation;
	}

	public function getConversationMessageContent(TranslatorInterface $translator): string
	{
		return sprintf(
			'Un coup d\'état a échoué. %s demeure le dirigeant de %s',
			$this->leader?->name,
			$translator->trans(sprintf('factions.%d.name.popular', $this->putschist->faction->identifier)),
		);
	}

	public function getConversationMessageAuthor(): Player
	{
		return $this->factionAccount;
	}

	public function getNotificationBuilders(
		UrlGeneratorInterface $urlGenerator,
		TranslatorInterface $translator,
	): \Generator {
		yield NotificationBuilder::new()
			->setTitle('Votre coup d\'état a échoué')
			->setContent(NotificationBuilder::paragraph(
				'Le peuple ne vous a pas soutenu, l\'ancien gouvernement reste en place.'
			))
			->forPlayer($this->putschist)
		;

		if (null !== $this->leader) {
			yield NotificationBuilder::new()
				->setTitle('Un coup d\'état a échoué')
				->setContent(NotificationBuilder::paragraph(
					$translator->trans(sprintf(
						'factions.%d.status.%d',
						$this->putschist->faction->identifier,
						$this->putschist->status->value,
					), ['gender' => $this->putschist->getGender()]),
					' ',
					NotificationBuilder::link(
						$urlGenerator->generate('embassy', ['player' => $this->putschist->id]),
						$this->putschist->name,
					),
					' a tenté un coup d\'état, celui-ci a échoué.',
				))
				->forPlayer($this->leader);
		}
	}
}
