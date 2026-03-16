<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election;

use App\Modules\Demeter\Model\Election\Candidate;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Candidate>
 */
class CandidateFactory extends PersistentObjectFactory
{
	protected function defaults(): array|callable
	{
		return [
			'id' => Uuid::v4(),
			'program' => self::faker()->text(),
		];
	}

	public static function class(): string
	{
		return Candidate::class;
	}
}
