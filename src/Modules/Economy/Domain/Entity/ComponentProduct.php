<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ProductType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'economy__component_products')]
class ComponentProduct extends Product
{
	public function __construct(
		Uuid $id,
		Company $company,
		#[ORM\Column(name: 'component_type', type: 'string', enumType: ComponentType::class, length: 32)]
		public ComponentType $type,
		\DateTimeImmutable $createdAt,
		int $stockpile = 0,
	) {
		parent::__construct($id, $company, $createdAt, $stockpile);
	}

	public function getType(): ProductType
	{
		return ProductType::Component;
	}
}
