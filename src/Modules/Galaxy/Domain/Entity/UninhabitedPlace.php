<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

abstract class UninhabitedPlace extends Place
{
	public function __construct(
		Uuid $id,
		System $system,
		int $position,
		\DateTimeImmutable $updatedAt,
		#[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
		public int $resources,
		#[ORM\Column(type: 'float', options: ['unsigned' => true])]
		public float $credits,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $history,
	) {
		parent::__construct($id, $system, $position, $updatedAt);
	}
}
