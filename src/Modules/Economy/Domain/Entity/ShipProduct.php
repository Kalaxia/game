<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'economy__ship_products')]
class ShipProduct extends Product
{
	public function __construct(
		Uuid                   $id,
		Company                $company,
		#[ORM\Column(type: 'smallint', enumType: ShipCategory::class, length: 32)]
		public ShipCategory    $shipCategory,
		#[ORM\Column(length: 128, enumType: ShipProductSlug::class)]
		public ShipProductSlug $slug,
		\DateTimeImmutable     $createdAt,
	) {
		parent::__construct($id, $company, $createdAt);
	}

	public function getType(): ProductType
	{
		return ProductType::Ship;
	}
}
