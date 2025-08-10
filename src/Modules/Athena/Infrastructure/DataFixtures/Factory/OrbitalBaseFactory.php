<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\DataFixtures\Factory;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\PlaceFactory;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<OrbitalBase>
 */
class OrbitalBaseFactory extends PersistentProxyObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'place' => PlaceFactory::randomOrCreate(),
			'player' => PlayerFactory::randomOrCreate(),
			'name' => 'Colonie',
			'typeOfBase' => OrbitalBase::TYP_NEUTRAL,
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
		return OrbitalBase::class;
	}
}
