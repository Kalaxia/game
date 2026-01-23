<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Mandate
{
	public function __construct(
		public Uuid $id,
		public Color $faction,
		public Player $leader,
		public \DateTimeImmutable $startedAt,
		public \DateTimeImmutable $expiredAt,
	) {

	}
}
