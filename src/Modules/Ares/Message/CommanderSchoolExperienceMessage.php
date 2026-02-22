<?php

declare(strict_types=1);

namespace App\Modules\Ares\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

readonly class CommanderSchoolExperienceMessage implements AsyncMessage
{
	public function __construct(public Uuid $commanderId)
	{
	}
}
