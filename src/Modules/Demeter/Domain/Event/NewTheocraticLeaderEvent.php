<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use Psr\Log\LoggerInterface;

class NewTheocraticLeaderEvent extends NewLeaderEvent
{
	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionName} has a new theocratic leader: {newLeaderName}.', [
			'factionName' => $this->factionName,
			'newLeaderName' => $this->newLeader->name,
		]);
	}

	public function getConversationMessageContent(): string
	{
		return 'Les Oracles ont parlé, un nouveau dirigeant va faire valoir la force de '.
			$this->factionName.
			' à travers la galaxie. Longue vie à <strong>'.
			$this->newLeader->name.
			'</strong>.<br /><br /><br /><br />';
	}

	public function getNotificationBuilder(): NotificationBuilder
	{
		return NotificationBuilder::new()
			->setTitle('Vous avez été nommé Guide')
			->setContent(NotificationBuilder::paragraph(
				'Les Oracles ont parlé, vous êtes désigné par la Grande Lumière pour guider Cardan vers la Gloire.'
			));
	}
}
