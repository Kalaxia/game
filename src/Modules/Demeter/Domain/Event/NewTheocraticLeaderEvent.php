<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewTheocraticLeaderEvent extends NewLeaderEvent
{
	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionIdentifier} has a new theocratic leader: {newLeaderName}.', [
			'factionIdentifier' => $this->faction->identifier,
			'newLeaderName' => $this->newLeader->name,
		]);
	}

	public function getConversationMessageContent(TranslatorInterface $translator): string
	{
		return 'Les Oracles ont parlé, un nouveau dirigeant va faire valoir la force de '.
			$translator->trans(sprintf('factions.%d.name.popular', $this->faction->identifier)).
			' à travers la galaxie. Longue vie à <strong>'.
			$this->newLeader->name.
			'</strong>.<br /><br /><br /><br />';
	}

	public function getNotificationBuilders(
		UrlGeneratorInterface $urlGenerator,
		TranslatorInterface $translator,
	): \Generator {
		yield NotificationBuilder::new()
			->setTitle('Vous avez été nommé Guide')
			->setContent(NotificationBuilder::paragraph(
				'Les Oracles ont parlé, vous êtes désigné par la Grande Lumière pour guider ',
				$translator->trans(sprintf('factions.%d.name.popular', $this->faction->identifier)),
				' vers la Gloire.'
			))
			->forPlayer($this->newLeader)
		;
	}
}
