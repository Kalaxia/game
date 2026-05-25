<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Hermes\Application\Builder\NewsBuilder;
use App\Modules\Hermes\Domain\Enum\NewsType;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Domain\Event\NewsEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\LoggerEvent;

abstract class NewLeaderEvent implements LoggerEvent, NotificationEvent, ConversationMessageEvent, NewsEvent
{
	public function __construct(
		public Color $faction,
		public Player $newLeader,
		public PoliticalEvent $politicalEvent,
		protected Player $factionPlayer,
		protected Conversation $factionConversation,
		protected array $candidatesData,
	) {
	}

	public function getConversation(): Conversation
	{
		return $this->factionConversation;
	}

	public function getConversationMessageAuthor(): Player
	{
		return $this->factionPlayer;
	}

	public function getNewsBuilders(array $factions): \Generator
	{
		$data = [
			'factionIdentifier' => $this->politicalEvent->faction->identifier,
			'factionRegime' => $this->politicalEvent->faction->regime,
			'leaderId' => $this->newLeader->id,
			'leaderName' => $this->newLeader->name,
			'leaderGender' => $this->newLeader->getGender(),
			'candidates_data' => $this->candidatesData,
		];

		yield NewsBuilder::new()
			->setType(NewsType::NewLeader)
			->fromFaction($this->faction)
			->forFaction($this->faction)
			->setData($data)
		;

		yield NewsBuilder::new()
			->setType(NewsType::ForeignNewLeader)
			->fromFaction($this->faction)
			->forFactions(array_filter(
				$factions,
				fn (Color $f) => $f->identifier !== $this->faction->identifier,
			))
			->setData($data);
	}
}
