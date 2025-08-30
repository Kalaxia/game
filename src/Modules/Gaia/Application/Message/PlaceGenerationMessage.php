<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

class PlaceGenerationMessage implements AsyncMessage
{
	public function __construct(
		public Uuid $systemId,
		public int $position,
		public int $sectorDanger,
	) {

	}
}
