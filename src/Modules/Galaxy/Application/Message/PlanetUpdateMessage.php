<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

readonly class PlanetUpdateMessage implements AsyncMessage
{
	public function __construct(public Uuid $planetId)
	{
	}
}
