<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Message;

use App\Shared\Domain\Message\AsyncMessage;

class SystemGenerationMessage implements AsyncMessage
{
	public function __construct(
		public int $sectorIdentifier,
		public int $sectorDanger,
		public int $xPosition,
		public int $yPosition,
		public int $typeOfSystem,
	) {
	}
}
