<?php

namespace App\Modules\Zeus\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

class CanAfford extends Constraint
{
	public function __construct(
		public int $price,
		array|null $groups = null,
		mixed $payload = null
	) {
		parent::__construct(
			groups: $groups,
			payload: $payload,
		);
	}
}
