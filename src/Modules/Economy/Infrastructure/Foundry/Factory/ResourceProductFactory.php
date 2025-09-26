<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Foundry\Factory;

use App\Modules\Economy\Domain\Entity\ResourceProduct;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ResourceType;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

class ResourceProductFactory extends PersistentProxyObjectFactory
{
	protected function defaults(): array|callable
	{
		$activity = self::faker()->randomElement(Activity::cases());

		return [
			'id' => Uuid::v4(),
			'company' => CompanyFactory::randomOrCreate([
				'activity' => $activity,
			]),
			'slug' => ResourceType::fromActivity($activity),
			'createdAt' => new \DateTimeImmutable(),
		];
	}

	public static function class(): string
	{
		return ResourceProduct::class;
	}
}
