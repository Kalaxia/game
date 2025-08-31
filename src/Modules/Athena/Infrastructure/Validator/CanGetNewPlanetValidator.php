<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Infrastructure\Validator\DTO\HasBasesCount;
use App\Modules\Promethee\Infrastructure\Validator\DTO\HasTechnology;
use App\Modules\Promethee\Model\TechnologyId;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CanGetNewPlanetValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof CanGetNewPlanet) {
			throw new UnexpectedTypeException($constraint, CanGetNewPlanet::class);
		}

		if (!$value instanceof HasTechnology) {
			throw new UnexpectedValueException($value, HasTechnology::class);
		}

		if (!$value instanceof HasBasesCount) {
			throw new UnexpectedValueException($value, HasBasesCount::class);
		}

		$technology = $value->getTechnology();
		$basesCount = $value->getBasesCount();

		$maxBasesQuantity = $technology->getTechnology(TechnologyId::BASE_QUANTITY) + 1;

		if ($basesCount >= $maxBasesQuantity) {
			$this->context
				->buildViolation('Vous avez assez de conquête en cours ou un niveau d\'administration étendue trop faible.')
				->addViolation();
		}
	}
}
