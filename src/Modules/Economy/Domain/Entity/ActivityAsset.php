<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

class ActivityAsset
{
	public function __construct(
		public int $id,
		public PlanetActivity $planetActivity,
		public Asset $asset,
		public float $quantity,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $updatedAt,
	) {
	}
}
