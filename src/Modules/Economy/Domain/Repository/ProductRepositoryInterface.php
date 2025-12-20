<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Repository;

use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Product>
 */
interface ProductRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Product|null;
}
