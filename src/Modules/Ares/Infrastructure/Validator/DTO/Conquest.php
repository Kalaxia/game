<?php

namespace App\Modules\Ares\Infrastructure\Validator\DTO;

use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Infrastructure\Validator\DTO\HasBasesCount;
use App\Modules\Gaia\Infrastructure\Validator\DTO\HasPlace;
use App\Modules\Gaia\Model\Place;
use App\Modules\Promethee\Infrastructure\Validator\DTO\HasTechnology;
use App\Modules\Promethee\Model\Technology;

readonly class Conquest implements HasCommander, HasPlace, HasBasesCount, HasTechnology
{
	public function __construct(
		private Commander  $commander,
		private Technology $attackerTechnology,
		private int        $attackerBasesCount,
		private Place      $targetedPlace,
	) {

	}

	public function getCommander(): Commander
	{
		return $this->commander;
	}

	public function getPlace(): Place
	{
		return $this->targetedPlace;
	}

	public function getBasesCount(): int
	{
		return $this->attackerBasesCount;
	}

	public function getTechnology(): Technology
	{
		return $this->attackerTechnology;
	}
}
