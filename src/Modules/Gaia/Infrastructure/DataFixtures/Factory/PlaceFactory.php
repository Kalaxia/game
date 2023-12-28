<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\DataFixtures\Factory;

use App\Modules\Gaia\Model\Place;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ModelFactory;

class PlaceFactory extends ModelFactory
{
	protected function getDefaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'player' => null,
			'base' => null,
			'system' => SystemFactory::randomOrCreate(),
			'typeOfPlace' => Place::TERRESTRIAL,
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

	protected static function getClass(): string
	{
		return Place::class;
	}
}
