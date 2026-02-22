<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Forum;

use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'forumTopic')]
class ForumTopic
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\Column(type: 'string', length: 255)]
		public string $title,
		#[ORM\ManyToOne]
		public Player $player,
		#[ORM\Column(type: 'smallint')]
		public int $forum,
		#[ORM\ManyToOne]
		public Color $faction,
		// TODO check if this property is used
		#[ORM\Column(type: 'smallint')]
		public int $statement = 1,
		#[ORM\Column(type: 'smallint')]
		public int $messagesCount = 0,
		#[ORM\Column(type: 'boolean')]
		public bool $isUp = false,
		#[ORM\Column(type: 'boolean')]
		public bool $isClosed = false,
		#[ORM\Column(type: 'boolean')]
		public bool $isArchived = false,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $lastContributedAt = new \DateTimeImmutable(),
		// si joueur renseigné lors du chargement
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public ?Player $lastView = null,
	) {
	}
}
