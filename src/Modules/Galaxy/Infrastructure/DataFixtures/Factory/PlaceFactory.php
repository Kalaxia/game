<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures\Factory;

use App\Modules\Galaxy\Domain\Entity\Asteroid;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\Ruin;
use App\Modules\Galaxy\Domain\Enum\PlanetType;
use App\Modules\Galaxy\Domain\Service\Planet\DeterminePlanetResourceCoefficients;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Planet>
 */
class PlaceFactory extends PersistentProxyObjectFactory
{
	public function __construct(
		private readonly PropertyAccessorInterface $propertyAccessor,
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
			'history' => self::faker()->numberBetween(15, 45),
			'resources' => self::faker()->numberBetween(0, 50000),
			'credits' => self::faker()->numberBetween(0, 50000),
			'danger' => self::faker()->numberBetween(0, 100),
			'maxDanger' => self::faker()->numberBetween(20, 100),
			'updatedAt' => new \DateTimeImmutable(),
		];
	}

	protected function initialize(): static
	{
		return $this->instantiateWith(function (array $attributes) {
			$class = self::faker()->randomElement([
				Planet::class,
				Asteroid::class,
				Ruin::class,
			]);

			$classProperties = array_column(new \ReflectionClass($class)->getProperties(), 'name');

			return new $class(...array_filter(
				$attributes,
				fn (string $attributeName) => in_array($attributeName, $classProperties),
				ARRAY_FILTER_USE_KEY,
			));
		})->afterInstantiate(function (Place $place): void {
			if ($place instanceof Planet) {
				$place->naturalResources = ($this->determinePlanetResourceCoefficients)($place->planetType);
			}
		});
	}

	public static function class(): string
	{
		return Place::class;
	}
}
