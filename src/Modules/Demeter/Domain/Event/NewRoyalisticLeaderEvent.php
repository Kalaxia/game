<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Zeus\Model\Player;
use Psr\Log\LoggerInterface;

class NewRoyalisticLeaderEvent extends NewLeaderEvent
{
	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionName} has a new royalistic leader: {newLeaderName}.', [
			'factionName' => $this->factionName,
			'newLeaderName' => $this->newLeader->name,
		]);
	}

	public function getConversationMessageContent(): string
	{
		return sprintf(
			'Un putsch a réussi, un nouveau dirigeant va faire valoir la force de %s
			à travers la galaxie. Longue vie à <strong>%s</strong>.<br /><br />
			De nombreux membres de la faction ont soutenu le mouvement révolutionnaire :<br /><br />
			%s a reçu le soutien de %f% de la population.<br />',
			$this->factionName,
			$this->newLeader->name,
			$this->newLeader->name,
			$this->candidatesData['supportPercentage'],
		);
	}

	public function getNotificationBuilder(): NotificationBuilder
	{
		return NotificationBuilder::new()
			->setTitle('Votre coup d\'état a réussi')
			->setContent(NotificationBuilder::paragraph(
				'Le peuple vous a soutenu, vous avez renversé le ',
				$this->factionStatuses[Player::CHIEF - 1],
				' de votre faction et avez pris sa place.',
			));
	}
}
