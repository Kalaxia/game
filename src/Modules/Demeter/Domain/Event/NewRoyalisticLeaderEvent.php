<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewRoyalisticLeaderEvent extends NewLeaderEvent
{
	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionIdentifier} has a new royalistic leader: {newLeaderName}.', [
			'factionIdentifier' => $this->faction->identifier,
			'newLeaderName' => $this->newLeader->name,
		]);
	}

	public function getConversationMessageContent(TranslatorInterface $translator): string
	{
		return sprintf(
			'Un putsch a réussi, un nouveau dirigeant va faire valoir la force de %s
			à travers la galaxie. Longue vie à <strong>%s</strong>.<br /><br />
			De nombreux membres de la faction ont soutenu le mouvement révolutionnaire :<br /><br />
			%s a reçu le soutien de %f%% de la population.<br />',
			$translator->trans(sprintf('factions.%d.name.popular', $this->faction->identifier)),
			$this->newLeader->name,
			$this->newLeader->name,
			$this->candidatesData['supportPercentage'],
		);
	}

	public function getNotificationBuilders(
		UrlGeneratorInterface $urlGenerator,
		TranslatorInterface $translator,
	): \Generator {
		yield NotificationBuilder::new()
			->setTitle('Votre coup d\'état a réussi')
			->setContent(NotificationBuilder::paragraph(
				'Le peuple vous a soutenu, vous avez renversé le ',
				$translator->trans(sprintf(
					'factions.%d.status.%d',
					$this->faction->identifier,
					PlayerStatus::Chief->value,
				)),
				' de votre faction et avez pris sa place.',
			))
			->forPlayer($this->newLeader)
		;
	}
}
