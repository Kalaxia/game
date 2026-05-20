<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\DataFixtures\Foundry\Factory;

use App\Modules\Ares\Model\Squadron;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Squadron>
 */
final class SquadronFactory extends PersistentObjectFactory
{
	protected function defaults(): array|callable
	{
		$date = \DateTimeImmutable::createFromMutable(self::faker()->dateTime);

		return [
			'id' => Uuid::v4(),
			'position' => self::faker()->unique()->numberBetween(0, 24),
			'createdAt' => $date,
			'updatedAt' => $date,
		];
	}

	public static function class(): string
	{
		return Squadron::class;
	}
}
