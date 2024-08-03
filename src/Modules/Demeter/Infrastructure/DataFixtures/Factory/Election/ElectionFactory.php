<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election;

use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Demeter\Model\Election\Election;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ModelFactory;

class ElectionFactory extends ModelFactory
{
	protected function getDefaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'faction' => FactionFactory::random(),
			'dElection' => new \DateTimeImmutable(),
		];
	}

	protected static function getClass(): string
	{
		return Election::class;
	}
}
