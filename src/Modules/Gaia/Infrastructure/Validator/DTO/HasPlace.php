<?php

namespace App\Modules\Gaia\Infrastructure\Validator\DTO;

use App\Modules\Gaia\Domain\Entity\Planet;

interface HasPlace
{
	public function getPlace(): Planet;
}
