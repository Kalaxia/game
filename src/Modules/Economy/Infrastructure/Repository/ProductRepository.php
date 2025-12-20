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
}
