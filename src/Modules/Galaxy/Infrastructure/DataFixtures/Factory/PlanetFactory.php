<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures\Factory;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Planet>
 */
class PlanetFactory extends PersistentProxyObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'player' => null,
			'name' => 'Colonie',
			'typeOfBase' => Planet::BASE_TYPE_COLONY,
			'points' => 0,
			'iSchool' => 1000,
			'iAntiSpy' => 0,
			'antiSpyAverage' => 0,
			'shipStorage' => [],
			'resourcesStorage' => 5000,
			'createdAt' => new \DateTimeImmutable(),
			'updatedAt' => new \DateTimeImmutable(),
		];
	}

	public static function class(): string
	{
		return Planet::class;
	}
}
