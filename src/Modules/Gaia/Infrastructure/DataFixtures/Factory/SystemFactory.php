<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\DataFixtures\Factory;

use App\Modules\Gaia\Model\System;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<System>
 */
class SystemFactory extends PersistentProxyObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'sector' => SectorFactory::randomOrCreate(),
			'faction' => null,
			'xPosition' => self::faker()->numberBetween(0, 100),
			'yPosition' => self::faker()->numberBetween(0, 100),
			'typeOfSystem' => 1,
		];
	}

	public static function class(): string
	{
		return System::class;
	}
}
