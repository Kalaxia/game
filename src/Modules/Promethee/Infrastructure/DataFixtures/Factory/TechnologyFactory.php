<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Infrastructure\DataFixtures\Factory;

use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Technology>
 */
class TechnologyFactory extends PersistentProxyObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'player' => PlayerFactory::random(),
		];
	}

	public static function class(): string
	{
		return Technology::class;
	}
}
