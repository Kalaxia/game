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
		#[ORM\Column(length: 128, enumType: ResourceType::class)]
		public ResourceType $slug,
		\DateTimeImmutable $createdAt,
	) {
		parent::__construct($id, $company, $createdAt);
	}

	public function getType(): ProductType
	{
		return ProductType::Resource;
	}
}
