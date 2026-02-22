<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\SequentiallyValidator;

class CanMakeBuilding extends Sequentially
{
	public function __construct(
		int $buildingQueuesCount,
	) {
		parent::__construct([
			new HasRightPlanetType(),
			new HasUnlockedBuilding(),
			new IsValidTargetLevel(),
			new CanOrderBuilding($buildingQueuesCount),
		]);
	}

	#[\Override]
	public function validatedBy(): string
	{
		return SequentiallyValidator::class;
	}
}
