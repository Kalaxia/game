<?php

declare(strict_types=1);

namespace App\Modules\Ares\Model;

class Ship
{
	public function __construct(
		public int $id,
		public int $shipNumber,
		public int $life,
		public Squadron $squadron,
	) {
	}
}
