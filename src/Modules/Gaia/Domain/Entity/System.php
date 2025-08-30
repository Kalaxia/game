<?php

namespace App\Modules\Gaia\Domain\Entity;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;

class System
{
	public function __construct(
		public Uuid $id,
		public Sector|null $sector,
		public Color|null $faction,
		public int $xPosition,
		public int $yPosition,
		public int $typeOfSystem,
	) {
			
	}
}
