<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Validator\DTO;

use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Infrastructure\Validator\DTO\HasBasesCount;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Infrastructure\Validator\DTO\HasPlace;
use App\Modules\Promethee\Infrastructure\Validator\DTO\HasTechnology;
use App\Modules\Promethee\Model\Technology;

readonly class Conquest implements HasCommander, HasPlace, HasBasesCount, HasTechnology
{
	public function __construct(
		private Commander  $commander,
		private Technology $attackerTechnology,
		private int        $attackerBasesCount,
		private Planet     $targetedPlace,
	) {

	}

	public function getCommander(): Commander
	{
		return $this->commander;
	}

	public function getPlace(): Planet
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
