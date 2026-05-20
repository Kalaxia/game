<?php

namespace App\Tests\Integration\Modules\Zeus\Application\Handler;

use App\Modules\Zeus\Application\Handler\UniversityInvestmentHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UniversityInvestmentHandlerTest extends KernelTestCase
{
	public function testSpend(): void
	{
		static::bootKernel();

		$handler = static::getContainer()->get(UniversityInvestmentHandler::class);

		static::markTestIncomplete('Not implemented');
	}
}
