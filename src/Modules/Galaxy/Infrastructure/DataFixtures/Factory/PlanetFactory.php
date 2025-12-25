<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures\Factory;

use App\Modules\Galaxy\Domain\Entity\Asteroid;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Enum\PlanetType;
use App\Modules\Galaxy\Domain\Service\Planet\DeterminePlanetActivities;
use App\Modules\Galaxy\Domain\Service\Planet\DeterminePlanetResourceCoefficients;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Planet>
 */
class PlanetFactory extends PersistentObjectFactory
{
	public function __construct(
		private readonly DeterminePlanetActivities $determinePlanetActivities,
		private readonly DeterminePlanetResourceCoefficients $determinePlanetResourceCoefficients,
	) {
		parent::__construct();
	}

	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'planetType' => self::faker()->randomElement(PlanetType::cases()),
			'player' => null,
			'name' => 'Colonie',
			'typeOfBase' => Planet::BASE_TYPE_COLONY,
			'points' => 0,
			'iSchool' => 1000,
			'iAntiSpy' => 0,
			'antiSpyAverage' => 0,
			'shipStorage' => [],
			'resourcesStorage' => 5000,
			'system' => SystemFactory::randomOrCreate(),
			'position' => self::faker()->randomNumber(1),
			'population' => self::faker()->numberBetween(50, 250),
			'danger' => self::faker()->numberBetween(0, 100),
			'maxDanger' => self::faker()->numberBetween(20, 100),
			'updatedAt' => new \DateTimeImmutable(),
		];
	}

	protected function initialize(): static
	{
		return $this->afterInstantiate(function (Planet $planet): void {
			$planet->naturalResources = ($this->determinePlanetResourceCoefficients)($planet->planetType);
		})->afterPersist(function (Place $planet): void {
			if ($planet->population > 0) {
				($this->determinePlanetActivities)($planet);
			}
		});
	}

	public static function class(): string
	{
		return Planet::class;
	}
}
