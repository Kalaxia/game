<?php

namespace App\Modules\Gaia\Infrastructure\Validator;

use App\Modules\Gaia\Infrastructure\Validator\DTO\HasPlace;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class BelongsToPlayerValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof BelongsToPlayer) {
			throw new UnexpectedTypeException($constraint, BelongsToPlayer::class);
		}

		if (!$value instanceof HasPlace) {
			throw new UnexpectedValueException($value, HasPlace::class);
		}

		$place = $value->getPlace();

		if (null === $place->base) {
			$this->context
				->buildViolation('This planet does not belong to a player')
				->addViolation();
		}
	}
}
