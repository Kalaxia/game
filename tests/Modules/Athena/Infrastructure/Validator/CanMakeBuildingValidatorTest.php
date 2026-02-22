<?php

declare(strict_types=1);

namespace App\Tests\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Infrastructure\Validator\CanMakeBuilding;
use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Athena\Infrastructure\Validator\HasUnlockedBuilding;
use App\Modules\Galaxy\Infrastructure\DataFixtures\Factory\PlanetFactory;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Promethee\Infrastructure\DataFixtures\Factory\TechnologyFactory;
use App\Shared\Infrastructure\DataFixtures\Story\SmallMapStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CanMakeBuildingValidatorTest extends KernelTestCase
{
	use Factories;
	use ResetDatabase;

	public function testValidator(/* OrbitalBase $base, Technology $technology, int $buildingIdentifier, int $targetLevel, ConstraintViolationListInterface $violationList, int $buildingQueuesCount = 0 */): void
	{
		static::markTestSkipped('Must fix usage of Foundry stories');

		static::bootKernel();
		/** @var ValidatorInterface $validator */
		$validator = static::getContainer()->get(ValidatorInterface::class);
		SmallMapStory::load();

		$base = PlanetFactory::createOne([
			'levelGenerator' => 9,
			'levelSpatioport' => 0,
		])->object();
		$targetLevel = 1;
		$technology = TechnologyFactory::createOne()->object();
		$buildingIdentifier = PlanetResource::SPATIOPORT;
		$buildingQueuesCount = 0;
		$violationList = [];

		$buildingConstructionOrder = new BuildingConstructionOrder(
			planet: $base,
			technology: $technology,
			buildingIdentifier: $buildingIdentifier,
			targetLevel: $targetLevel,
		);

		$violations = $validator->validate($buildingConstructionOrder, new CanMakeBuilding($buildingQueuesCount));

		static::assertCount(count($violationList), $violations);

		foreach ($violations as $violation) {
			static::assertInstanceOf(HasUnlockedBuilding::class, $violation->getConstraint());
		}
	}

	public function testValidatorManually(): void
	{
		static::markTestSkipped('Must fix usage of Foundry stories');

		static::bootKernel();
		/** @var ValidatorInterface $validator */
		$validator = static::getContainer()->get(ValidatorInterface::class);

		SmallMapStory::load();

		$base = PlanetFactory::createOne([
			'levelGenerator' => 9,
			'levelSpatioport' => 0,
		])->object();
		$technology = TechnologyFactory::createOne()->object();
		$buildingIdentifier = PlanetResource::SPATIOPORT;
		$targetLevel = 1;
		$violationList = [
		];

		$buildingConstructionOrder = new BuildingConstructionOrder(
			planet: $base,
			technology: $technology,
			buildingIdentifier: $buildingIdentifier,
			targetLevel: $targetLevel,
		);

		$violations = $validator->validate($buildingConstructionOrder, new CanMakeBuilding(0));

		static::assertCount(count($violationList), $violations);

		foreach ($violations as $violation) {
			static::assertInstanceOf(HasUnlockedBuilding::class, $violation->getConstraint());
		}
	}

	private function provideData(): \Generator
	{
		yield [
			PlanetFactory::new()->withoutPersisting()->create([
				'levelGenerator' => 9,
				'levelSpatioport' => 0,
			]),
			TechnologyFactory::new()->withoutPersisting()->create(),
			PlanetResource::SPATIOPORT,
			1,
			[
			],
		];
	}
}
