<?php

namespace App\Modules\Promethee\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

class HasUnlockedTechnology extends Constraint
{
	public function __construct(
		public int $technologyId,
		array $groups = null,
		mixed $payload = null
	) {
		parent::__construct([], $groups, $payload);
	}
}
