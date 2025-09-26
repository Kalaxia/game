<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Repository;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
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

	public function get(Uuid $id): Product|null
	{
		return $this->find($id);
	}

	public function searchOffers(
		int                       $systemXPosition,
		int                       $systemYPosition,
		int 					  $requestedQuantity,
		Activity|null             $activity = null,
		ProductType|null          $productType = null,
		ShipProductSlug|null      $shipProductSlug = null,
		ComponentProductSlug|null $componentProductSlug = null,
		ResourceType|null 		  $resourceType = null,
		int                       $maxDistance = 30,
		int                       $limit = 20,
		int                       $offset = 0,
	): array {
		$distanceFunction = "ST_Distance(
                ST_GeomFromText(CONCAT('POINT(', :x_position, ' ', :y_position, ')')),
                ST_GeomFromText(CONCAT('POINT(', s.x_position, ' ', s.y_position, ')'))
            )";

		$whereClauses = [];
		$parameters = [];

		if (null !== $activity) {
			$whereClauses[] = 'pa.activity = :activity';
			$parameters['activity'] = $activity->value;
		}

		if (null !== $productType) {
			$whereClauses[] = 'p.type = :productType';
			$parameters['productType'] = $productType->value;
		}

		if (null !== $shipProductSlug) {
			$whereClauses[] = 'p.slug = :slug';
			$parameters['slug'] = $shipProductSlug->value;
		}

		if (null !== $componentProductSlug) {
			$whereClauses[] = 'p.slug = :slug';
			$parameters['slug'] = $componentProductSlug->value;
		}

		if (null !== $resourceType) {
			$whereClauses[] = 'p.slug = :slug';
			$parameters['slug'] = $resourceType->value;
		}

		if (empty($whereClauses)) {
			throw new \InvalidArgumentException('At least one filter must be provided.');
		}

		$whereClause = implode(' AND ', $whereClauses);

		$statement = $this->getEntityManager()->getConnection()->prepare(
			<<<SQL
				SELECT p.id as productId, p.type as productType, c.name as companyName, f.identifier as factionIdentifier,
				       p.component_type as componentType, p.ship_category as shipCategory,
				       p.slug as productSlug,
				       s.x_position as systemXPosition, s.y_position as systemYPosition, sec.identifier as sectorIdentifier,
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
				WHERE ($distanceFunction <= :max_distance AND $whereClause)
				ORDER BY distance
				LIMIT $limit OFFSET $offset;
			SQL
		);

		return array_map(
			fn (array $row) => new Offer(
				productId: Uuid::fromString($row['productId']),
				productType: ProductType::from($row['productType']),
				componentType: (null !== $row['componentType']) ? ComponentType::from($row['componentType']) : null,
				componentProductSlug: (null !== $row['componentType']) ? ComponentProductSlug::from($row['productSlug']) : null,
				resourceType: (ProductType::Resource->value === $row['productType']) ? ResourceType::from($row['productSlug']) : null,
				shipCategory: (null !== $row['shipCategory']) ? ShipCategory::from($row['shipCategory']) : null,
				shipProductSlug: (null !== $row['shipCategory']) ? ShipProductSlug::from($row['productSlug']) : null,
				companyName: $row['companyName'],
				requestedQuantity: $requestedQuantity,
				factionIdentifier: $row['factionIdentifier'],
				distance: intval(round($row['distance'])),
				systemXPosition: $row['systemXPosition'],
				systemYPosition: $row['systemYPosition'],
				sectorIdentifier: $row['sectorIdentifier'],
			),
			$statement->executeQuery(array_merge($parameters, [
				'x_position' => $systemXPosition,
				'y_position' => $systemYPosition,
				'max_distance' => $maxDistance,
			]))->fetchAllAssociative(),
		);
	}
}
