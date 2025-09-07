<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Galaxy\Helper\PlanetHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class HasResourcesForBuildingValidator extends ConstraintValidator
{
	public function __construct(private readonly PlanetHelper $planetHelper)
	{
	}

	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof HasResourcesForBuilding) {
			throw new UnexpectedTypeException($constraint, HasResourcesForBuilding::class);
		}

		if (!$value instanceof BuildingConstructionOrder) {
			throw new UnexpectedValueException($value, BuildingConstructionOrder::class);
		}

		if ($value->getPlanet()->resourcesStorage < $this->getNeededResources($value)) {
			$this->context
				->buildViolation('Cette base ne dispose pas de suffisamment de ressources')
				->addViolation();
		}
	}

	private function getNeededResources(BuildingConstructionOrder $buildingConstructionOrder): int
	{
		return $this->planetHelper->getBuildingInfo(
			$buildingConstructionOrder->getBuildingIdentifier(),
			'level',
			$buildingConstructionOrder->getTargetLevel(),
			'resourcePrice',
		);
	}
}
