<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election;

use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Demeter\Model\Election\Election;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Election>
 */
class ElectionFactory extends PersistentObjectFactory
{
	protected function defaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'faction' => FactionFactory::random(),
			'dElection' => new \DateTimeImmutable(),
		];
	}

	public static function class(): string
	{
		return Election::class;
	}
}
