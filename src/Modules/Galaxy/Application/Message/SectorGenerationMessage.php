<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Message;

use App\Shared\Domain\Message\AsyncMessage;

class SectorGenerationMessage implements AsyncMessage
{
	public function __construct(
		public int $identifier,
		public string|null $name,
		public int|null $factionIdentifier,
		public int $xPosition,
		public int $yPosition,
		public int $xBarycenter,
		public int $yBarycenter,
		public int $danger,
		public int $points,
	) {
	}
}
