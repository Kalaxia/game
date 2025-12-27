<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\DataFixtures\Factory;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Planet>
 */
class PlanetFactory extends PersistentObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'place' => PlaceFactory::randomOrCreate(),
			'player' => PlayerFactory::randomOrCreate(),
			'name' => 'Colonie',
			'typeOfBase' => Planet::BASE_TYPE_COLONY,
			'levelGenerator' => 1,
			'levelRefinery' => 1,
			'levelDock1' => 1,
			'levelDock2' => 0,
			'levelDock3' => 0,
			'levelTechnosphere' => 1,
			'levelCommercialPlateforme' => 0,
			'levelStorage' => 1,
			'levelRecycling' => 0,
			'levelSpatioport' => 0,
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
