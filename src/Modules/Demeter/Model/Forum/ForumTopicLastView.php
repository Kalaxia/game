<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Forum;

use App\Modules\Zeus\Model\Player;

class ForumTopicLastView
{
	public function __construct(
		public Player $player,
		public ForumTopic $forumTopic,
		public \DateTimeImmutable $viewedAt,
	) {
	}
}
