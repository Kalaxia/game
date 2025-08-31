<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class HasUnlockedBuildingValidator extends ConstraintValidator
{
	public function __construct(
		private readonly BuildingLevelHandler $buildingLevelHandler,
		private readonly TechnologyHelper $technologyHelper,
	) {
	}

	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof HasUnlockedBuilding) {
			throw new UnexpectedTypeException($constraint, HasUnlockedBuilding::class);
		}

		if (!$value instanceof BuildingConstructionOrder) {
			throw new UnexpectedValueException($value, BuildingConstructionOrder::class);
		}

		$this->checkGeneratorRequirement($value);
		$this->checkTechnologyRequirement($value);
	}

	private function checkGeneratorRequirement(BuildingConstructionOrder $value): void
	{
		$neededGeneratorLevel = $this->buildingLevelHandler->getRequiredGeneratorLevel($value->getBuildingIdentifier());

		if (!$neededGeneratorLevel > $value->getPlanet()->levelGenerator) {
			$this->context->buildViolation('Le générateur n\'a pas le niveau requis')
				->addViolation();
		}
	}

	private function checkTechnologyRequirement(BuildingConstructionOrder $buildingConstructionOrder): void
	{
		$data = PlanetResource::$building[$buildingConstructionOrder->getBuildingIdentifier()];

		if (!array_key_exists('techno', $data)) {
			return;
		}


		if (1 !== $buildingConstructionOrder->getTechnology()->getTechnology($data['techno'])) {
			$this->context
				->buildViolation(sprintf(
					'il vous faut développer la technologie %s',
					$this->technologyHelper->getInfo($data['techno'], 'name'),
				))
				->addViolation();
		}
	}
}
