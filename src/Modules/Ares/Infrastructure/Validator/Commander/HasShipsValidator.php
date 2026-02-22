<?php

namespace App\Modules\Ares\Infrastructure\Validator\Commander;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Infrastructure\Validator\DTO\HasCommander;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class HasShipsValidator extends ConstraintValidator
{
	public function __construct(
		private readonly CommanderArmyHandler $commanderArmyHandler,
	) {
	}

	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof HasShips) {
			throw new UnexpectedTypeException($constraint, HasShips::class);
		}

		if (!$value instanceof HasCommander) {
			throw new UnexpectedValueException($value, HasCommander::class);
		}

		$commander = $value->getCommander();

		if (0 === $this->commanderArmyHandler->getPev($commander)) {
			$this->context
				->buildViolation('Vous devez affecter au moins un vaisseau à votre officier.')
				->addViolation();
		}
	}
}
