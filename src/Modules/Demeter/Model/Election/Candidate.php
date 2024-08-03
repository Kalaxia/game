<?php

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Candidate
{
	public function __construct(
		public Uuid $id,
		public Election $election,
		public Player $player,
		public string $program,
		public Player|null $chiefChoice = null,
		public Player|null $treasurerChoice = null,
		public Player|null $warlordChoice = null,
		public Player|null $ministerChoice = null,
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
	) {
	}
}
