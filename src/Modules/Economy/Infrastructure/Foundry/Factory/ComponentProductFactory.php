<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Foundry\Factory;

use App\Modules\Economy\Domain\Entity\ComponentProduct;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ComponentType;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

class ComponentProductFactory extends PersistentProxyObjectFactory
{
	protected function defaults(): array|callable
	{
		$activity = self::faker()->randomElement([
			Activity::PropulsorFactory,
			Activity::ShieldFactory,
			Activity::WeaponFactory,
		]);

		return [
			'id' => Uuid::v4(),
			'company' => CompanyFactory::randomOrCreate([
				'activity' => $activity,
			]),
			'slug' => self::faker()->randomElement(
				ComponentProductSlug::fromComponentType(
					ComponentType::fromActivity($activity)
				)
			),
			'createdAt' => new \DateTimeImmutable(),
		];
	}

	public static function class(): string
	{
		return ComponentProduct::class;
	}
}
