<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Handler;

use App\Modules\Gaia\Application\Message\SystemGenerationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SystemGenerationHandler
{
	public function __invoke(SystemGenerationMessage $message): void
	{
		// TODO: Implement __invoke() method.
	}
}
