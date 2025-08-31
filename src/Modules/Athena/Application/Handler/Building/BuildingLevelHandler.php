<?php

namespace App\Modules\Athena\Application\Handler\Building;

use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Resource\PlanetResource;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BuildingLevelHandler
{
	public function increaseBuildingLevel(Planet $planet, int $buildingIdentifier): void
	{
		$this->updateBuildingLevel($planet, $buildingIdentifier, fn (int $level) => $level + 1);
	}

	public function decreaseBuildingLevel(Planet $planet, int $buildingIdentifier): void
	{
		$this->updateBuildingLevel($planet, $buildingIdentifier, fn (int $level) => max($level - 1, 0));
	}

	private function updateBuildingLevel(Planet $planet, int $buildingIdentifier, callable $updateLevel): void
	{
		$propertyAccessor = PropertyAccess::createPropertyAccessor();
		$levelField = $this->getBuildingLevelField($buildingIdentifier);

		$propertyAccessor->setValue(
			$planet,
			$levelField,
			$updateLevel($propertyAccessor->getValue($planet, $levelField)),
		);
	}

	public function getBuildingLevel(Planet $planet, int $buildingIdentifier): int
	{
		$propertyAccessor = PropertyAccess::createPropertyAccessor();

		return $propertyAccessor->getValue($planet, $this->getBuildingLevelField($buildingIdentifier));
	}

	public function getBuildingRealLevel(Planet $planet, int $buildingIdentifier, array $buildingQueues): int
	{
		return array_reduce(
			$buildingQueues,
			function (int $level, BuildingQueue $buildingQueue) use ($buildingIdentifier) {
				if ($buildingIdentifier !== $buildingQueue->buildingNumber) {
					return $level;
				}

				return ($level < $buildingQueue->targetLevel) ? $buildingQueue->targetLevel : $level;
			},
			$this->getBuildingLevel($planet, $buildingIdentifier),
		);
	}

	public function getRequiredGeneratorLevel(int $buildingIdentifier): int
	{
		return match ($buildingIdentifier) {
			PlanetResource::GENERATOR,
			PlanetResource::STORAGE,
			PlanetResource::DOCK1,
			PlanetResource::REFINERY,
			PlanetResource::TECHNOSPHERE => 0,
			PlanetResource::DOCK2, PlanetResource::SPATIOPORT => 20,
			PlanetResource::DOCK3 => 30,
			PlanetResource::COMMERCIAL_PLATEFORME, PlanetResource::RECYCLING => 10,
			// no break
			default => throw new \LogicException('Invalid building Identifier'),
		};
	}

	private function getBuildingLevelField(int $buildingIdentifier): string
	{
		$buildingName = PlanetResource::$building[$buildingIdentifier]['name']
			?? throw new \LogicException(sprintf('Building identifier %s is not valid', $buildingIdentifier));

		return sprintf('level%s', ucfirst($buildingName));
	}
}
