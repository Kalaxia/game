<?php

namespace App\Tests\Integration\Modules\Zeus\Application\Handler;

use App\Modules\Zeus\Application\Handler\CommanderWageHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommanderWageHandlerTest extends KernelTestCase
{
	public function test(): void
	{
		static::bootKernel();

		$commanderWageHandler = static::getContainer()->get(CommanderWageHandler::class);

		static::markTestIncomplete('Not implemented');
	}
}
