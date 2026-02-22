<?php

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;

class Election
{
	public function __construct(
		public Uuid $id,
		public Color $faction,
		public \DateTimeImmutable $dElection,
	) {
	}
}
