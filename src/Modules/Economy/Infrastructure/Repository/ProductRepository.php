<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Repository;

use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Product>
 */
class ProductRepository extends DoctrineRepository implements ProductRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Product::class);
	}
}
