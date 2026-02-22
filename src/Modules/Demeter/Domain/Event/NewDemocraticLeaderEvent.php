<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Classes\Library\Format;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Zeus\Model\Player;
use Psr\Log\LoggerInterface;

class NewDemocraticLeaderEvent extends NewLeaderEvent
{
	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionName} has a new democratic leader: {newLeaderName}.', [
			'factionName' => $this->factionName,
			'newLeaderName' => $this->newLeader->name,
		]);
	}

	public function getConversationMessageContent(): string
	{
		return sprintf(
			'La période électorale est terminée.
						Un nouveau dirigeant a été élu pour faire valoir la force de %s à travers la galaxie.
						Gloire à <strong>%s</strong>.<br /><br />Voici les résultats des élections :<br /><br />
						%s',
			$this->factionName,
			$this->newLeader->name,
			implode('<br>', array_map(
				/** @param array{candidate: Candidate, votes_count: int} $player */
				fn (array $player) => sprintf(
					'%s a reçu %d vote%s',
					$player['candidate']->player->name,
					$player['votes_count'],
					Format::plural($player['votes_count']),
				),
				$this->candidatesData,
			)),
		);
	}

	public function getNotificationBuilder(): NotificationBuilder
	{
		return NotificationBuilder::new()
			->setTitle('Votre avez été élu')
			->setContent(NotificationBuilder::paragraph(sprintf(
				'Le peuple vous a soutenu, vous avez été élu %s de votre faction.',
				$this->factionStatuses[Player::CHIEF - 1],
			)));
	}
}
