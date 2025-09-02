<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Entity;

use App\Modules\Gaia\Domain\Enum\PlaceType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'galaxy__uninhabited_places')]
class UninhabitedPlace extends Place
{
	public function __construct(
		Uuid $id,
		System $system,
		PlaceType $typeOfPlace,
		int $position,
		\DateTimeImmutable $updatedAt,
		#[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
		public int $resources,
		#[ORM\Column(type: 'integer', options: ['unsigned' => true])]
		public float $credits,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $history,
	) {
	}
}
