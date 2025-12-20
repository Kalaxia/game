<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures\Factory;

use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Enum\SystemType;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<System>
 */
class SystemFactory extends PersistentObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'sector' => SectorFactory::randomOrCreate(),
			'faction' => null,
			'xPosition' => self::faker()->numberBetween(0, 100),
			'yPosition' => self::faker()->numberBetween(0, 100),
			'typeOfSystem' => SystemType::YellowDwarf,
		];
	}

	public static function class(): string
	{
		return System::class;
	}
}
