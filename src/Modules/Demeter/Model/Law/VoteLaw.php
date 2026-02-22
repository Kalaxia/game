<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Law;

use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'voteLaw')]
#[ORM\UniqueConstraint(name: 'vote_unique_key', columns: ['law_id', 'player_id'])]
class VoteLaw
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Law $law,
		#[ORM\ManyToOne]
		public Player $player,
		#[ORM\Column(type: 'smallint')]
		public int $vote,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $votedAt,
	) {
	}
}
