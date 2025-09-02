<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Entity;

use App\Modules\Gaia\Domain\Enum\PlaceType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'galaxy__gas_pockets')]
class GasPocket extends UninhabitedPlace
{
	public function getType(): PlaceType
	{
		return PlaceType::GasPocket;
	}
}
