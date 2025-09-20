<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Repository;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Entity\ComponentProduct;
use App\Modules\Economy\Domain\Entity\PlanetActivity;
use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Economy\Domain\Entity\ResourceProduct;
use App\Modules\Economy\Domain\Entity\ShipProduct;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Product>
 */
class ProductRepository extends DoctrineRepository implements ProductRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Product::class);
	}

	public function searchProducts(
		Activity $activity,
		System $system,
		int $maxDistance = 30,
		int $limit = 20,
		int $offset = 0,
	): array {
		$distanceFunction = "ST_Distance(
                ST_GeomFromText(CONCAT('POINT(', :x_position, ' ', :y_position, ')')),
                ST_GeomFromText(CONCAT('POINT(', s.x_position, ' ', s.y_position, ')'))
            )";

		$statement = $this->getEntityManager()->getConnection()->prepare(
			<<<SQL
				SELECT p.id as productId, p.type as productType, c.name as companyName, f.identifier as factionIdentifier,
				       p.component_type as componentType, p.resource_type as resourceType, p.ship_category as shipCategory,
				       sec.identifier as sectorIdentifier,
			    $distanceFunction as distance
				FROM economy__products p
				INNER JOIN economy__companies c ON p.company_id = c.id
				LEFT JOIN economy__planet_activities pa ON (pa.company_id = c.id)
				INNER JOIN galaxy__planets pl ON pa.planet_id = pl.id
				INNER JOIN galaxy__places place ON pl.id = place.id
				INNER JOIN galaxy__systems s ON place.system_id = s.id
				INNER JOIN galaxy__sectors sec ON s.sector_id = sec.id
				LEFT JOIN player player ON player.id = pl.player_id
				LEFT JOIN color f ON player.faction_id = f.id
				WHERE ($distanceFunction <= :max_distance AND pa.activity = :activity)
				ORDER BY distance
				LIMIT $limit OFFSET $offset;
			SQL
		);

		return array_map(
			fn (array $row) => new Offer(
				Uuid::fromString($row['productId']),
				ProductType::from($row['productType']),
				(null !== $row['componentType']) ? ComponentType::from($row['componentType']) : null,
				(null !== $row['resourceType']) ? ResourceType::from($row['resourceType']) : null,
				(null !== $row['shipCategory']) ? ShipCategory::from($row['shipCategory']) : null,
				$row['companyName'],
				$row['factionIdentifier'],
				intval(round($row['distance'])),
				$row['sectorIdentifier'],
			),
			$statement->executeQuery([
				'x_position' => $system->xPosition,
				'y_position' => $system->yPosition,
				'max_distance' => $maxDistance,
				'activity' => $activity->value,
			])->fetchAllAssociative(),
		);
	}
}
