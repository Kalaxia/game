<?php

namespace App\Modules\Ares\Infrastructure\Validator\Commander;

use App\Modules\Ares\Infrastructure\Validator\DTO\HasCommander;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Infrastructure\Validator\DTO\HasPlace;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NotAllyTargetValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof NotAllyTarget) {
			throw new UnexpectedTypeException($constraint, NotAllyTarget::class);
		}

		if (!$value instanceof HasCommander) {
			throw new UnexpectedValueException($value, HasCommander::class);
		}

		if (!$value instanceof HasPlace) {
			throw new UnexpectedValueException($value, HasPlace::class);
		}

		$commander = $value->getCommander();
		$place = $value->getPlace();
		$faction = $commander->player->faction;

		if (null === $place->base) {
			return;
		}

		if ($faction->id->equals($place->player->faction->id) || Color::ALLY === $faction->relations[$place->player->faction->identifier]) {
			$this->context
				->buildViolation('Vous ne pouvez pas attaquer un lieu appartenant à votre Faction ou d\'une faction alliée.')
				->addViolation();
		}
	}
}
