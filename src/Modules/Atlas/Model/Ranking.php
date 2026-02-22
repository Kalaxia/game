<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Model;

use Symfony\Component\Uid\Uuid;

class Ranking
{
	public function __construct(
		public Uuid $id,
		public \DateTimeImmutable $createdAt,
	) {
	}
}
