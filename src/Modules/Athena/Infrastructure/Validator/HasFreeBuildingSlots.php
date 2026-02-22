<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

class HasFreeBuildingSlots extends Constraint
{
	public function __construct(
		public readonly int $buildingQueuesCount,
		?array $groups = null,
		mixed $payload = null,
	) {
		parent::__construct(
			groups: $groups,
			payload: $payload,
		);
	}
}
