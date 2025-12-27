<?php

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Validator\Constraint;

class CanBeInvaded extends Constraint
{
	public function __construct(
		public Player|null $targetedPlayer = null,
		?array $groups = null,
		mixed $payload = null
	) {
		parent::__construct(
			groups: $groups,
			payload: $payload,
		);
	}
}
