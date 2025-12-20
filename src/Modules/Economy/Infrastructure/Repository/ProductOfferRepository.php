<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Repository;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use App\Modules\Economy\Domain\Repository\ProductOfferRepositoryInterface;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

class ProductOfferRepository implements ProductOfferRepositoryInterface
{
	public function __construct(private EntityManagerInterface $entityManager)
	{
	}

	public function get(Uuid $id): Offer|null
	{
		$statement  = $this->prepareQuery('p.id = :id', 1, 0);
		$statement->bindValue('id', $id, UuidType::NAME);

		$result = $statement->executeQuery()->fetchAssociative();

		if (false === $result) {
			return null;
		}

		return $this->hydrateOffer(
			$result,
			1,
		);
	}

	public function search(
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

		$statement = $this->prepareQuery($whereClause, $limit, $offset);

		return array_map(
			fn (array $row) => $this->hydrateOffer($row, $requestedQuantity),

			$statement->executeQuery(array_merge($parameters, [
				'x_position' => $systemXPosition,
				'y_position' => $systemYPosition,
			]))->fetchAllAssociative(),
		);
	}

	private function prepareQuery(string $whereClause, int $limit, int $offset): Statement
	{
		return $this->entityManager->getConnection()->prepare(<<<SQL
			SELECT p.id as productId, p.type as productType, c.name as companyName, f.identifier as factionIdentifier,
				   p.component_type as componentType, p.ship_category as shipCategory,
				   p.slug as productSlug,
				   s.x_position as systemXPosition, s.y_position as systemYPosition, sec.identifier as sectorIdentifier
			FROM economy__products p
			INNER JOIN economy__companies c ON p.company_id = c.id
			LEFT JOIN economy__planet_activities pa ON (pa.company_id = c.id)
			INNER JOIN galaxy__planets pl ON pa.planet_id = pl.id
			INNER JOIN galaxy__places place ON pl.id = place.id
			INNER JOIN galaxy__systems s ON place.system_id = s.id
			INNER JOIN galaxy__sectors sec ON s.sector_id = sec.id
			LEFT JOIN player player ON player.id = pl.player_id
			LEFT JOIN color f ON player.faction_id = f.id
			WHERE $whereClause
			LIMIT $limit OFFSET $offset;
		SQL);
	}

	private function hydrateOffer(array $data, int $requestedQuantity): Offer
	{
		return new Offer(
			productId: Uuid::fromString($data['productId']),
			productType: ProductType::from($data['productType']),
			componentType: (null !== $data['componentType']) ? ComponentType::from($data['componentType']) : null,
			componentProductSlug: (null !== $data['componentType']) ? ComponentProductSlug::from($data['productSlug']) : null,
			resourceType: (ProductType::Resource->value === $data['productType']) ? ResourceType::from($data['productSlug']) : null,
			shipCategory: (null !== $data['shipCategory']) ? ShipCategory::from($data['shipCategory']) : null,
			shipProductSlug: (null !== $data['shipCategory']) ? ShipProductSlug::from($data['productSlug']) : null,
			companyName: $data['companyName'],
			requestedQuantity: $requestedQuantity,
			factionIdentifier: $data['factionIdentifier'],
			systemXPosition: $data['systemXPosition'],
			systemYPosition: $data['systemYPosition'],
			sectorIdentifier: $data['sectorIdentifier'],
		);
	}
}
