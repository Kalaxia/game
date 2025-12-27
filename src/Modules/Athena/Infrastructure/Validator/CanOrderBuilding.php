<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use Symfony\Component\Validator\Constraints\Compound;

class CanOrderBuilding extends Compound
{
	public function __construct(private readonly int $buildingQueuesCount)
	{
		parent::__construct();
	}

	public function getConstraints(array $options): array
	{
		return [
			new HasResourcesForBuilding(),
			new HasFreeBuildingSlots($this->buildingQueuesCount),
		];
	}
}
