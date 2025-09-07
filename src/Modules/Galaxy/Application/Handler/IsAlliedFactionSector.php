<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Handler;

use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Sector;

class IsAlliedFactionSector
{
	public function __invoke(Sector $sector, Color $faction): bool
	{
		return $sector->faction?->id->equals($faction->id)
			|| Color::ALLY === $sector->faction?->relations[$faction->identifier];
	}
}
