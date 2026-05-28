<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Putsch;
use App\Modules\Hermes\Application\Builder\NewsBuilder;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Enum\NewsType;
use App\Modules\Hermes\Domain\Event\NewsEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Zeus\Infrastructure\Validator\IsActiveFactionPlayer;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\LoggerEvent;
use App\Shared\Domain\Event\MessengerEvent;
use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use App\Shared\Domain\Message\AsyncMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class NewPutschAttemptEvent implements LoggerEvent, NotificationEvent, MessengerEvent, NewsEvent
{
	public function __construct(
		private Putsch $putsch,
		private Candidate $candidate,
		private ?Player $currentLeader = null,
	) {
	}

	public function log(LoggerInterface $logger): void
	{
		$logger->info('Faction {factionName} has a new putsch attempt.', [
			'factionName' => $this->putsch->faction->identifier,
		]);
	}

	public function getMessage(): AsyncMessage|AsyncHighPriorityMessage
	{
		return new BallotMessage($this->putsch->faction->id);
	}

	public function getDelay(): ?\DateTimeImmutable
	{
		return $this->putsch->endedAt;
	}

	public function getNotificationBuilders(
		UrlGeneratorInterface $urlGenerator,
		TranslatorInterface $translator,
	): \Generator {
		yield NotificationBuilder::new()
			->setTitle('Coup d\'Etat.')
			->withRecipientSpecification(new IsActiveFactionPlayer($this->putsch->faction))
			->setContent(NotificationBuilder::paragraph(
				'Un membre de votre Faction soulève une partie du peuple et tente un coup d\'état contre le gouvernement.',
				NotificationBuilder::divider(),
				NotificationBuilder::link(
					$urlGenerator->generate('view_faction_election'),
					'prendre parti sur le coup d\'état.',
				),
			));
	}

	public function getNewsBuilders(array $factions): \Generator
	{
		$data = [
			'factionIdentifier' => $this->putsch->faction->identifier,
			'playerId' => $this->candidate->player->id,
			'playerName' => $this->candidate->player->name,
			'playerGender' => $this->candidate->player->getGender(),
			'playerStatus' => $this->candidate->player->status,
		];

		if (null !== $this->currentLeader) {
			$data['leaderId'] = $this->currentLeader->id;
			$data['leaderName'] = $this->currentLeader->name;
			$data['leaderGender'] = $this->currentLeader->name;
		}

		yield NewsBuilder::new()
			->fromFaction($this->putsch->faction)
			->setType(NewsType::PutschAttempt)
			->setData($data)
			->forFaction($this->putsch->faction);

		yield NewsBuilder::new()
			->fromFaction($this->putsch->faction)
			->setType(NewsType::ForeignPutschAttempt)
			->setData($data)
			->forFactions(array_filter(
				$factions,
				fn (Color $f) => $f->identifier !== $this->putsch->faction->identifier,
			));
	}
}
