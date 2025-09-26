<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Foundry\Factory;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Entity\ShipProduct;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

class ShipProductFactory extends PersistentProxyObjectFactory
{
	protected function defaults(): array|callable
	{
		$shipCategory = self::faker()->randomElement(ShipCategory::cases());

		return [
			'id' => Uuid::v4(),
			'company' => CompanyFactory::randomOrCreate([
				'activity' => Activity::Shipyard,
			]),
			'shipCategory' => $shipCategory,
			'slug' => ShipProductSlug::fromShipCategory($shipCategory),
			'createdAt' => new \DateTimeImmutable(),
		];
	}

	protected function initialize(): static
	{
		return $this->beforeInstantiate(fn (array $parameters): array => array_merge($parameters, [
			'slug' => ShipProductSlug::fromShipCategory($parameters['shipCategory'])
		]));
	}

	public static function class(): string
	{
		return ShipProduct::class;
	}
}
