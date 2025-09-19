<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Repository;

use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<Product>
 */
interface ProductRepositoryInterface extends EntityRepositoryInterface
{

}
