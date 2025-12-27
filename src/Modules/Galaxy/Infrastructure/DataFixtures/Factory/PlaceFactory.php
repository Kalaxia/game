<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures\Factory;

use App\Modules\Galaxy\Domain\Entity\Planet;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Planet>
 */
class PlaceFactory extends PersistentObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'player' => null,
			'base' => null,
			'system' => SystemFactory::randomOrCreate(),
			'position' => self::faker()->randomNumber(1),
			'population' => self::faker()->numberBetween(50, 250),
			'coefResources' => self::faker()->numberBetween(30, 95),
			'coefHistory' => self::faker()->numberBetween(15, 45),
			'resources' => self::faker()->numberBetween(0, 50000),
			'danger' => self::faker()->numberBetween(0, 100),
			'maxDanger' => self::faker()->numberBetween(20, 100),
			'updatedAt' => new \DateTimeImmutable(),
		];
	}

	public static function class(): string
	{
		return Planet::class;
	}
}
