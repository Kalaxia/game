<?php

namespace App\Modules\Gaia\Domain\Entity;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;

class Sector
{
	public function __construct(
		public Uuid $id,
		public int $identifier,
		public Color|null $faction,
		public int $xPosition,
		public int $yPosition,
		public int $xBarycentric,
		public int $yBarycentric,
		public int $tax,
		public string|null $name,
		public int $points,
		public int $population,
		// @TODO find the use of this field
		public int $lifePlanet,
		public bool $prime,
	) {
	}
}
