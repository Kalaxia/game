<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Forum;

use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'forumMessage')]
class ForumMessage
{
	public const PUBLISHED = 1;
	public const HIDDEN = 2;
	public const FORBIDDEN_FLOOD = 3;
	public const FORBIDDEN_INSULT = 4;
	public const FORBIDDEN_PR0N = 5;
	public const FORBIDDEN_RACISM = 6;

	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Player $player,
		#[ORM\ManyToOne]
		public ForumTopic $topic,
		#[ORM\Column(type: 'text')]
		public string $oContent,
		#[ORM\Column(type: 'text')]
		public string $pContent,
		#[ORM\Column(type: 'smallint')]
		public int $statement,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt,
		#[ORM\Column(type: 'datetime_immutable', nullable: true)]
		public \DateTimeImmutable|null $updatedAt = null,
	) {
	}
}
