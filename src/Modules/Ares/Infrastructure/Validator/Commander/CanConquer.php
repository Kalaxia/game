<?php

namespace App\Modules\Ares\Infrastructure\Validator\Commander;

use App\Modules\Athena\Infrastructure\Validator\CanGetNewPlanet;
use App\Modules\Galaxy\Infrastructure\Validator\BelongsToPlayer;
use App\Modules\Promethee\Infrastructure\Validator\HasUnlockedTechnology;
use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Zeus\Infrastructure\Validator\CanAfford;
use Symfony\Component\Validator\Constraints\Compound;

class CanConquer extends Compound
{
	public function __construct(private readonly int $price)
	{
		parent::__construct();
	}

	protected function getConstraints(array $options): array
	{
		return [
			new HasUnlockedTechnology(TechnologyId::CONQUEST),
			new IsInOrbit(),
			new IsInRange(),
			new HasShips(),
			new BelongsToPlayer(),
			new NotAllyTarget(),
			new CanGetNewPlanet(),
			new CanAfford($this->price),
		];
	}
}
