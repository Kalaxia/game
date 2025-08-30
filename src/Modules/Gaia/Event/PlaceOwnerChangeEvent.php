<?php

namespace App\Modules\Gaia\Event;

use App\Modules\Gaia\Domain\Entity\Place;

class PlaceOwnerChangeEvent
{
	/** @var Place * */
	protected $place;

	public const NAME = 'gaia.place_owner_change';

	public function __construct(Place $place)
	{
		$this->place = $place;
	}

	/**
	 * @return Place
	 */
	public function getPlace()
	{
		return $this->place;
	}
}
