<?php

namespace App\Modules\Galaxy\Event;

use App\Modules\Galaxy\Domain\Entity\Planet;

class PlaceOwnerChangeEvent
{
	public const NAME = 'galaxy.place_owner_change';

	public function __construct(public Planet $planet)
	{
	}
}
