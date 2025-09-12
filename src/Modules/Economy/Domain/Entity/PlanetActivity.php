<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Galaxy\Domain\Entity\Planet;

class PlanetActivity
{
	public function __construct(
		public int $id,
		public Planet $orbitalBase,
		public Activity $activity,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $updatedAt,
	) {
	}
}
