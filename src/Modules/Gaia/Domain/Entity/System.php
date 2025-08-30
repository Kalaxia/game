<?php

namespace App\Modules\Gaia\Domain\Entity;

use App\Modules\Demeter\Model\Color;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'galaxy__systems')]
class System
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne(targetEntity: Sector::class)]
		public Sector $sector,
		#[ORM\ManyToOne(targetEntity: Color::class)]
		#[ORM\JoinColumn(nullable: true)]
		public Color|null $faction,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $xPosition,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $yPosition,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $typeOfSystem,
	) {
			
	}
}
