<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election;

use App\Modules\Demeter\Model\Election\DemocraticElection;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DemocraticElection>
 */
class DemocraticElectionFactory extends PersistentObjectFactory
{
	protected function defaults(): array|callable
	{
		return [
			'id' => Uuid::v4(),
		];
	}

	public static function class(): string
	{
		return DemocraticElection::class;
	}
}
