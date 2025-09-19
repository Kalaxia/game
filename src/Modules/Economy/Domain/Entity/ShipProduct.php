<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Enum\ProductType;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'economy__ship_products')]
class ShipProduct extends Product
{
	public function __construct(
		Uuid $id,
		Company $company,
		#[ORM\Column(type: 'string', enumType: ShipCategory::class, length: 32)]
		public ShipCategory $shipCategory,
		\DateTimeImmutable $createdAt,
		int $stockpile = 0,
	) {
		parent::__construct($id, $company, $createdAt, $stockpile);
	}

	public function getType(): ProductType
	{
		return ProductType::Ship;
	}
}
