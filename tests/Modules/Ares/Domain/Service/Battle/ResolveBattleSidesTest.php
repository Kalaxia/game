<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Service\Battle\BattleSidesResolution;
use App\Modules\Ares\Domain\Service\Battle\FactionHostilityInterface;
use App\Modules\Ares\Domain\Service\Battle\ResolveBattleSides;
use PHPUnit\Framework\TestCase;

class ResolveBattleSidesTest extends TestCase
{
	private const int REBELS = 0;
	private const int KALANKAR = 1;
	private const int VALKAR = 2;
	private const int FALKIRR = 3;
	private const int ADRANITE = 4;

	public function testNoBattleWithoutEnemies(): void
	{
		$resolution = $this->resolve([], [self::KALANKAR, self::VALKAR]);

		static::assertFalse($resolution->hasBattle());
		static::assertSame([[self::KALANKAR]], $resolution->sides);
		static::assertSame([self::VALKAR], $resolution->uninvolvedFactions);
	}

	public function testTwoEnemiesFightEachOther(): void
	{
		$resolution = $this->resolve([[self::KALANKAR, self::VALKAR]], [self::KALANKAR, self::VALKAR]);

		static::assertTrue($resolution->hasBattle());
		static::assertSame([[self::KALANKAR], [self::VALKAR]], $resolution->sides);
		static::assertSame([], $resolution->uninvolvedFactions);
	}

	public function testEnemyOfOneSideJoinsTheOtherSide(): void
	{
		// Falkirr is at war with Valkar only: it fights alongside Kalankar, whether allied or neutral
		$resolution = $this->resolve(
			[[self::KALANKAR, self::VALKAR], [self::FALKIRR, self::VALKAR]],
			[self::KALANKAR, self::VALKAR, self::FALKIRR],
		);

		static::assertSame([[self::KALANKAR, self::FALKIRR], [self::VALKAR]], $resolution->sides);
	}

	public function testEnemyOfEverySideFightsEveryone(): void
	{
		$resolution = $this->resolve(
			[[self::KALANKAR, self::VALKAR], [self::FALKIRR, self::VALKAR], [self::FALKIRR, self::KALANKAR]],
			[self::KALANKAR, self::VALKAR, self::FALKIRR],
		);

		static::assertSame([[self::KALANKAR], [self::VALKAR], [self::FALKIRR]], $resolution->sides);
	}

	public function testFactionFriendlyToEveryBelligerentStaysOut(): void
	{
		$resolution = $this->resolve(
			[[self::KALANKAR, self::VALKAR]],
			[self::KALANKAR, self::FALKIRR, self::VALKAR],
		);

		static::assertSame([[self::KALANKAR], [self::VALKAR]], $resolution->sides);
		static::assertSame([self::FALKIRR], $resolution->uninvolvedFactions);
	}

	public function testFactionGetsInvolvedOnceItsEnemyArrives(): void
	{
		// Falkirr arrives before Valkar, its enemy: it must still join the battle against Valkar
		$resolution = $this->resolve(
			[[self::KALANKAR, self::VALKAR], [self::FALKIRR, self::VALKAR]],
			[self::KALANKAR, self::FALKIRR, self::VALKAR],
		);

		static::assertSame([[self::KALANKAR, self::FALKIRR], [self::VALKAR]], $resolution->sides);
		static::assertSame([], $resolution->uninvolvedFactions);
	}

	public function testUninvolvedFactionsAtWarWithEachOtherAreLeftForAnotherBattle(): void
	{
		$resolution = $this->resolve(
			[[self::KALANKAR, self::VALKAR], [self::FALKIRR, self::ADRANITE]],
			[self::KALANKAR, self::VALKAR, self::FALKIRR, self::ADRANITE],
		);

		static::assertSame([[self::KALANKAR], [self::VALKAR]], $resolution->sides);
		static::assertSame([self::FALKIRR, self::ADRANITE], $resolution->uninvolvedFactions);
	}

	public function testReinforcementJoinsItsSideInAnOngoingBattle(): void
	{
		$resolution = $this->resolve(
			[[self::KALANKAR, self::VALKAR]],
			[self::VALKAR],
			[[self::KALANKAR], [self::VALKAR]],
		);

		static::assertSame([[self::KALANKAR], [self::VALKAR]], $resolution->sides);
	}

	public function testRebelsAreEnemiesOfEveryone(): void
	{
		$resolution = $this->resolve([], [self::REBELS, self::KALANKAR, self::VALKAR]);

		static::assertSame([[self::REBELS], [self::KALANKAR, self::VALKAR]], $resolution->sides);
	}

	/**
	 * @param list<array{int, int}> $wars
	 * @param list<int>             $factions
	 * @param list<list<int>>       $currentSides
	 */
	private function resolve(array $wars, array $factions, array $currentSides = []): BattleSidesResolution
	{
		$hostility = new class($wars) implements FactionHostilityInterface {
			/**
			 * @param list<array{int, int}> $wars
			 */
			public function __construct(private array $wars)
			{
			}

			public function areEnemies(int $faction, int $otherFaction): bool
			{
				if ($faction === $otherFaction) {
					return false;
				}

				if (0 === $faction || 0 === $otherFaction) {
					return true;
				}

				return in_array([$faction, $otherFaction], $this->wars, true)
					|| in_array([$otherFaction, $faction], $this->wars, true);
			}
		};

		return (new ResolveBattleSides($hostility))($factions, $currentSides);
	}
}
