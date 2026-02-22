<?php

namespace App\Modules\Galaxy\Domain\Entity;

use App\Modules\Demeter\Model\Color;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'galaxy__sectors')]
#[ORM\UniqueConstraint(name: 'sector_identifier', columns: ['identifier'])]
class Sector
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $identifier,
		#[ORM\ManyToOne(targetEntity: Color::class)]
		public ?Color $faction,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $xPosition,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $yPosition,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $xBarycentric,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $yBarycentric,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $tax,
		#[ORM\Column(type: 'string', length: 255, nullable: true)]
		public ?string $name,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 1])]
		public int $points,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $population,
		#[ORM\Column(type: 'boolean', options: ['default' => false])]
		public bool $prime,
	) {
	}
}
