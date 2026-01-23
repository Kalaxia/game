<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Domain\Event\ConversationMessageEvent;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Model\Player;

readonly class MissingCandidatesEvent implements ConversationMessageEvent
{
	public function __construct(
		public string $factionName,
		public Player $factionAccount,
		public Conversation $factionConversation,
		public int $regime,
		public Player|null $currentLeader,
	) {
	}

	public function getConversation(): Conversation
	{
		return $this->factionConversation;
	}

	public function getConversationMessageContent(): string
	{
		// TODO put the translations in translations file and transform the interface to give the translation key and the parameters separately
		if ($this->regime === Color::REGIME_THEOCRATIC) {
			return 'Nul ne s\'est soumis au regard des dieux pour conduire ' .
				$this->factionName . ' vers sa gloire.' .
				(null !== $this->currentLeader)
					? $this->currentLeader->name.' demeure l\'élu des dieux pour accomplir leurs desseins dans la galaxie.'
					: 'Par conséquent, le siège du pouvoir demeure vacant.';
		}

		return 'La période électorale est terminée. Aucun candidat ne s\'est présenté pour prendre la tête de '.
			$this->factionName.'.'.
			(null !== $this->currentLeader)
				? '<br>Par conséquent, '.$this->currentLeader->name.' est toujours au pouvoir.'
				: '<br>Par conséquent, le siège du pouvoir demeure vacant.';
	}

	public function getConversationMessageAuthor(): Player
	{
		return $this->factionAccount;
	}

}
