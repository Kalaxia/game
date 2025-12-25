<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Economy\Domain\Enum\ProductType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\Table(name: 'economy__products')]
#[ORM\DiscriminatorColumn(name: 'type', enumType: ProductType::class, length: 24)]
#[ORM\DiscriminatorMap([
	ProductType::Component->value => ComponentProduct::class,
	ProductType::Ship->value => ShipProduct::class,
	ProductType::Resource->value => ResourceProduct::class,
])]
abstract class Product
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne(targetEntity: Company::class)]
		public Company $company,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt,
	) {

	}

	abstract public function getType(): ProductType;
}

