<?php

namespace App\Modules\Athena\Domain\DTO;

readonly class CommercialRouteIncome
{
	public function __construct(
		public int $initial,
		public int $bonus,
		public int $total,
	) {
	}
}
