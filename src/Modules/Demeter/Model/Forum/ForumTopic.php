<?php

namespace App\Modules\Demeter\Model\Forum;

use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class ForumTopic
{
	public function __construct(
		public Uuid $id,
		public string $title,
		public Player $player,
		public int $forum,
		public Color $faction,
		// TODO check if this property is used
		public int $statement = 1,
		public int $messagesCount = 0,
		public bool $isUp = false,
		public bool $isClosed = false,
		public bool $isArchived = false,
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
		public \DateTimeImmutable $lastContributedAt = new \DateTimeImmutable(),
		// si joueur renseigné lors du chargement
		public ?Player $lastView = null,
	) {
	}
}
