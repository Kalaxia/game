<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Entity;

use App\Modules\Galaxy\Domain\Enum\PlaceType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'galaxy__ruins')]
class Ruin extends UninhabitedPlace
{
	public function getType(): PlaceType
	{
		return PlaceType::Ruin;
	}
}
