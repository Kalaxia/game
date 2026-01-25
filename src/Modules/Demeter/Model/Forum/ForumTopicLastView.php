<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Forum;

use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'forum__topics_last_views')]
class ForumTopicLastView
{
	public function __construct(
		#[ORM\Id]
		#[ORM\ManyToOne]
		public Player $player,
		#[ORM\Id]
		#[ORM\ManyToOne]
		public ForumTopic $forumTopic,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $viewedAt,
	) {
	}
}
