<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Message;

use Symfony\Component\Uid\Uuid;

class PlaceGenerationMessage
{
	public function __construct(
		public Uuid $systemId,

	) {

	}
}
