<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'politics__votes')]
#[ORM\UniqueConstraint(name: 'vote_unique_key', columns: ['candidate_id', 'player_id'])]
class Vote
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Candidate $candidate,
		#[ORM\ManyToOne]
		public Player $player,
		#[ORM\Column(type: 'boolean')]
		public bool $hasApproved,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $votedAt,
	) {
	}
}
