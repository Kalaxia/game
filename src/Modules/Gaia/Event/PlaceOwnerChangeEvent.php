<?php

namespace App\Modules\Gaia\Event;

use App\Modules\Gaia\Domain\Entity\Planet;

class PlaceOwnerChangeEvent
{
	public const NAME = 'gaia.place_owner_change';

	public function __construct(public Planet $planet)
	{
	}
}
