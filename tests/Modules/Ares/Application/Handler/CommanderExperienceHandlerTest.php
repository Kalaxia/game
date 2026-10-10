<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Application\Handler;

use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Tests\Modules\Ares\Support\BuildCommander;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Officer traits are drawn at levels 5, 10 and 15 (D20).
 */
class CommanderExperienceHandlerTest extends TestCase
{
	public function testATraitIsDrawnAtLevelsFiveTenAndFifteen(): void
	{
		$commander = BuildCommander::build(null, null, level: 1);
		$handler = $this->createHandler();

		$handler->upExperience($commander, 2 ** 4 * 100);
		static::assertSame(5, $commander->level);
		static::assertCount(1, $commander->traits);

		$handler->upExperience($commander, 2 ** 15 * 100);
		static::assertGreaterThanOrEqual(15, $commander->level);
		static::assertCount(OfficerTrait::MAX_PER_OFFICER, $commander->traits);
		static::assertSame($commander->traits, array_values(array_unique($commander->traits)));
		static::assertCount(3, $commander->getTraits());
	}

	public function testANewRecruitHasNoTrait(): void
	{
		$commander = BuildCommander::build(null, null, level: 0);

		$this->createHandler()->upExperience($commander, 2 ** 6 * 100, drawTraits: false);

		static::assertGreaterThanOrEqual(5, $commander->level);
		static::assertSame([], $commander->traits);
	}

	public function testBattleExperienceGoesToTheOfficerAndAHundredthToThePlayer(): void
	{
		$player = BuildCommander::player(1, 2);
		$commander = BuildCommander::build($player, null, level: 1);
		$playerManager = $this->createMock(PlayerManager::class);
		$playerManager->expects(static::once())->method('increaseExperience')->with($player, 3);

		$this->createHandler($playerManager)->awardBattleExperience($commander, 250);

		static::assertSame(250, $commander->experience);
		static::assertSame(250, $commander->earnedExperience);
		static::assertSame(2, $commander->level);
	}

	private function createHandler(?PlayerManager $playerManager = null): CommanderExperienceHandler
	{
		return new CommanderExperienceHandler(
			$this->createStub(CommanderRepositoryInterface::class),
			$playerManager ?? $this->createStub(PlayerManager::class),
			100,
			new Randomizer(new Mt19937(1)),
		);
	}
}
