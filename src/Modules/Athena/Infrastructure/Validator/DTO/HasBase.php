<?php

namespace App\Modules\Athena\Infrastructure\Validator\DTO;

use App\Modules\Gaia\Domain\Entity\Planet;

interface HasBase
{
	public function getPlanet(): Planet;
}
