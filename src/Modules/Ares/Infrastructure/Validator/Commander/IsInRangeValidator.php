<?php

namespace App\Modules\Ares\Infrastructure\Validator\Commander;

use App\Modules\Ares\Infrastructure\Validator\DTO\HasCommander;
use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Galaxy\Application\Handler\IsAlliedFactionSector;
use App\Modules\Galaxy\Infrastructure\Validator\DTO\HasPlace;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsInRangeValidator extends ConstraintValidator
{
	public function __construct(
		private readonly GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		private readonly IsAlliedFactionSector $isAlliedFactionSector,
	) {

	}

	/**
	 * @param HasCommander&HasPlace $value
	 */
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof IsInRange) {
			throw new UnexpectedTypeException($constraint, IsInRange::class);
		}

		if (!$value instanceof HasPlace) {
			throw new UnexpectedValueException($value, HasPlace::class);
		}

		if (!$value instanceof HasCommander) {
			throw new UnexpectedValueException($value, HasCommander::class);
		}

		$commander = $value->getCommander();
		$targetedPlace = $value->getPlace();

		$length = ($this->getDistanceBetweenPlaces)($commander->base, $targetedPlace);
		$isAlliedSector = ($this->isAlliedFactionSector)($targetedPlace->system->sector, $commander->player->faction);

		if ($length > Commander::DISTANCEMAX && !$isAlliedSector) {
			$this->context
				->buildViolation('Cet emplacement est trop éloigné.')
				->addViolation();
		}
	}
}
