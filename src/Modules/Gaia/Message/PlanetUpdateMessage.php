<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

class PlanetUpdateMessage implements AsyncMessage
{
	public function __construct(
		public Uuid $planetId,
	) {

	}
}
