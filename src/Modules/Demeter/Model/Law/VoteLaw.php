<?php

namespace App\Modules\Demeter\Model\Law;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class VoteLaw
{
	public function __construct(
		public Uuid $id,
		public Law $law,
		public Player $player,
		public int $vote,
		public \DateTimeImmutable $votedAt,
	) {
	}
}
