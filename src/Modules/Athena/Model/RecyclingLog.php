<?php

namespace App\Modules\Athena\Model;

use Symfony\Component\Uid\Uuid;

class RecyclingLog
{
	public function __construct(
		public Uuid $id,
		public RecyclingMission $mission,
		public \DateTimeImmutable $createdAt,
		public int $resources = 0,
		public int $credits = 0,
		public int $ship0 = 0,
		public int $ship1 = 0,
		public int $ship2 = 0,
		public int $ship3 = 0,
		public int $ship4 = 0,
		public int $ship5 = 0,
		public int $ship6 = 0,
		public int $ship7 = 0,
		public int $ship8 = 0,
		public int $ship9 = 0,
		public int $ship10 = 0,
		public int $ship11 = 0,
	) {
	}
}
