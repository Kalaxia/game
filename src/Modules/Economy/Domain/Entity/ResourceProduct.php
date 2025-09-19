<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class ResourceProduct extends Product
{
	public function __construct(
		Uuid $id,
		Company $company,
		#[ORM\Column(name: 'resource_type', type: 'string', enumType: ResourceType::class, length: 32)]
		public ResourceType $resourceType,
		\DateTimeImmutable $createdAt,
		int $stockpile = 0,
	) {
		parent::__construct($id, $company, $createdAt, $stockpile);
	}

	public function getType(): ProductType
	{
		return ProductType::Resource;
	}
}
