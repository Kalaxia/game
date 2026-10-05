<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Infrastructure\Command;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\GetFactionBattleBonuses;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Ares\Domain\Service\Battle\ComputeBattleExperience;
use App\Modules\Ares\Infrastructure\Command\SimulateBattlesCommand;
use App\Tests\Modules\Ares\Domain\Service\Battle\BattleTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SimulateBattlesCommandTest extends BattleTestCase
{
	public function testReportsWinRatesAndDurations(): void
	{
		$command = new SimulateBattlesCommand(
			new CreateBattle(self::getRegistry(), new GetFactionBattleBonuses([]), battlefieldSize: 5, fleetAreaRadius: 4, formationRadius: 2),
			new BattleEngine(),
			new ComputeBattleExperience(),
			turnDuration: 10,
		);
		$tester = new CommandTester($command);

		$tester->execute(['--a' => 'HeavyCorvette:44,Corvette:28', '--b' => 'LightFighter:10', '--runs' => '5']);

		$tester->assertCommandIsSuccessful();
		static::assertSame(Command::SUCCESS, $tester->getStatusCode());
		static::assertMatchesRegularExpression('/Wins\s+100\.0 %\s+0\.0 %/', $tester->getDisplay());
		static::assertStringContainsString('Median', $tester->getDisplay());
		static::assertStringContainsString('Officer XP', $tester->getDisplay());
	}
}
