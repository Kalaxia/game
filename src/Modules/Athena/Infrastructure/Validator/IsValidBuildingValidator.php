<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Infrastructure\Validator\DTO\HasBuildingIdentifier;
use App\Modules\Gaia\Resource\PlanetResource;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsValidBuildingValidator extends ConstraintValidator
{
	/**
	 * @param HasBuildingIdentifier $value
	 */
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof IsValidBuilding) {
			throw new UnexpectedTypeException($constraint, IsValidBuilding::class);
		}

		if (!$value instanceof HasBuildingIdentifier) {
			throw new UnexpectedValueException($value, HasBuildingIdentifier::class);
		}

		if (!\in_array($value->getBuildingIdentifier(), PlanetResource::BUILDINGS)) {
			$this->context->buildViolation('Invalid building number')
				->addViolation();
		}
	}
}
