<?php

namespace App\Modules\Hermes\Model;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class ConversationUser
{
	// constante
	public const MAX_USERS = 25;

	public const US_ADMIN = 1;
	public const US_STANDARD = 2;

	public const CS_DISPLAY = 1;
	public const CS_ARCHIVED = 2;

	public function __construct(
		public Uuid $id,
		public Conversation $conversation,
		public Player $player,
		public ?\DateTimeImmutable $lastViewedAt,
		public int $playerStatus = self::US_STANDARD,
		public int $conversationStatus = self::CS_DISPLAY,
	) {
	}

	public static function getPlayerStatement(int $statement): string
	{
		return match ($statement) {
			self::US_ADMIN => 'gestionnaire',
			self::US_STANDARD => 'normal',
			default => 'status inconnu',
		};
	}
}
