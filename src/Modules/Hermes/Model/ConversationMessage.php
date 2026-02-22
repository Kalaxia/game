<?php

namespace App\Modules\Hermes\Model;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class ConversationMessage
{
	public const MESSAGE_BY_PAGE = 25;

	public const TY_STD = 1;
	public const TY_SYSTEM = 2;

	public function __construct(
		public Uuid $id,
		public Conversation $conversation,
		public Player $player,
		public string $content,
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
		public int $type = self::TY_STD,
		public ?\DateTimeImmutable $updatedAt = null,
	) {
	}
}
