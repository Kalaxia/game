<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Message;

class SectorGenerationMessage
{
	public function __construct(
		public int $identifier,
		public string|null $name,
		public int|null $factionIdentifier,
		public int $xPosition,
		public int $yPosition,
		public int $xBarycenter,
		public int $yBarycenter,
		public int $points,
	) {
	}
}
