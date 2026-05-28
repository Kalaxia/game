<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Classes\Library\Format;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewDemocraticLeaderEvent extends NewLeaderEvent
{
	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionIdentifier} has a new democratic leader: {newLeaderName}.', [
			'factionIdentifier' => $this->faction->identifier,
			'newLeaderName' => $this->newLeader->name,
		]);
	}

	public function getConversationMessageContent(TranslatorInterface $translator): string
	{
		return sprintf(
			'La période électorale est terminée.
						Un nouveau dirigeant a été élu pour faire valoir la force de %s à travers la galaxie.
						Gloire à <strong>%s</strong>.<br /><br />Voici les résultats des élections :<br /><br />
						%s',
			$translator->trans(sprintf('factions.%d.name.popular', $this->faction->identifier)),
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

	public function getNotificationBuilders(
		UrlGeneratorInterface $urlGenerator,
		TranslatorInterface $translator,
	): \Generator {
		yield NotificationBuilder::new()
			->setTitle('Votre avez été élu')
			->setContent(NotificationBuilder::paragraph(sprintf(
				'Le peuple vous a soutenu, vous avez été élu %s de votre faction.',
				$translator->trans(
					sprintf(
						'factions.%d.status.%d',
						$this->faction->identifier,
						PlayerStatus::Chief->value,
					),
					['gender' => $this->newLeader->getGender()],
				),
			)))
			->forPlayer($this->newLeader)
		;
	}
}
