<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Resource\PlanetResource;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class HasRightPlanetTypeValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof HasRightPlanetType) {
			throw new UnexpectedTypeException($constraint, HasRightPlanetType::class);
		}

		if (!$value instanceof BuildingConstructionOrder) {
			throw new UnexpectedValueException($value, BuildingConstructionOrder::class);
		}

		$planet = $value->getPlanet();
		$buildingId = $value->getBuildingIdentifier();
		$level = $value->getTargetLevel();

		if (1 === $level && Planet::BASE_TYPE_COLONY === $planet->typeOfBase && in_array($buildingId, [PlanetResource::SPATIOPORT, PlanetResource::DOCK2])) {
			$this->context
				->buildViolation('vous devez évoluer votre colonie pour débloquer ce bâtiment')
				->addViolation();
		}
	}
}
