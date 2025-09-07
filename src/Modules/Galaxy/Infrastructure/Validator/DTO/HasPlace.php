<?php

namespace App\Modules\Galaxy\Infrastructure\Validator\DTO;

use App\Modules\Galaxy\Domain\Entity\Planet;

interface HasPlace
{
	public function getPlace(): Planet;
}
