<?php

/**
 * Message Forum.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 06.10.13
 */

namespace App\Modules\Demeter\Model\Forum;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class ForumMessage
{
	public const PUBLISHED = 1;
	public const HIDDEN = 2;
	public const FORBIDDEN_FLOOD = 3;
	public const FORBIDDEN_INSULT = 4;
	public const FORBIDDEN_PR0N = 5;
	public const FORBIDDEN_RACISM = 6;

	public function __construct(
		public Uuid $id,
		public Player $player,
		public ForumTopic $topic,
		public string $oContent,
		public string $pContent,
		public int $statement,
		public \DateTimeImmutable $createdAt,
		public ?\DateTimeImmutable $updatedAt = null,
	) {
	}
}
