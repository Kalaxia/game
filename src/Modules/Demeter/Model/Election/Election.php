<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Demeter\Model\Color;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'election')]
class Election
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Color $faction,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $dElection,
	) {
	}
}
