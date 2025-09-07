<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures\Factory;

use App\Modules\Galaxy\Domain\Entity\Sector;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Sector>
 */
class SectorFactory extends PersistentProxyObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'identifier' => self::faker()->randomNumber(2),
			'faction' => null,
			'xPosition' => self::faker()->numberBetween(0, 100),
			'yPosition' => self::faker()->numberBetween(0, 100),
			'xBarycentric' => self::faker()->numberBetween(0, 100),
			'yBarycentric' => self::faker()->numberBetween(0, 100),
			'tax' => 5,
			'name' => null,
			'points' => self::faker()->numberBetween(1, 5),
			'population' => 0,
			'prime' => false,
		];
	}

	public static function class(): string
	{
		return Sector::class;
	}
}
