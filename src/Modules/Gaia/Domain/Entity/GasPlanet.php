<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Entity;

use App\Modules\Gaia\Domain\Enum\PlaceType;

class GasPlanet extends UninhabitedPlace
{
	public function getType(): PlaceType
	{
		return PlaceType::GasPlanet;
	}
}
