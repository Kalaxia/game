<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Athena\Model\OrbitalBase;

class PlanetActivity
{
	public function __construct(
		public int $id,
		public OrbitalBase $orbitalBase,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $updatedAt,
	) {
	}
}
