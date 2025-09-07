<?php

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Shared\Domain\Specification\Specification;

abstract class PlanetSpecification implements Specification
{
	public function __construct(protected Planet $planet)
	{
	}
}
