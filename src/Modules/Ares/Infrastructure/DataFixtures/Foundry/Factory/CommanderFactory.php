<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\DataFixtures\Foundry\Factory;

use App\Modules\Ares\Model\Commander;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Commander>
 */
final class CommanderFactory extends PersistentObjectFactory
{
	protected function defaults(): array|callable
	{
		return [
			'id' => Uuid::v4(),
			'level' => self::faker()->numberBetween(1, 25),
			'name' => 'Commander',
			'avatar' => 't3-c4',
			'enlistedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
		];
	}

	public static function class(): string
	{
		return Commander::class;
	}
}
