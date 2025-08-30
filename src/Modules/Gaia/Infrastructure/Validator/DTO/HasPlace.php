<?php

namespace App\Modules\Gaia\Infrastructure\Validator\DTO;

use App\Modules\Gaia\Domain\Entity\Place;

interface HasPlace
{
	public function getPlace(): Place;
}
