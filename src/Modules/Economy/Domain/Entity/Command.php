<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use Symfony\Component\Uid\Uuid;

class Command
{
	public function __construct(
		public Uuid $id,
		public PlanetActivity $buyer,
		public PlanetActivity $seller,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $updatedAt,
	) {
	}
}
