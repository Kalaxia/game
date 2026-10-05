<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Model\Battle\Grid;

use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Model\Battle\Grid\HexDirection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HexTest extends TestCase
{
	public function testCubeCoordinatesSumToZero(): void
	{
		$hex = new Hex(3, -5);

		static::assertSame(0, $hex->q + $hex->r + $hex->s());
	}

	#[DataProvider('provideDistances')]
	public function testDistance(Hex $from, Hex $to, int $expectedDistance): void
	{
		static::assertSame($expectedDistance, $from->distanceTo($to));
		static::assertSame($expectedDistance, $to->distanceTo($from));
	}

	/**
	 * @return \Generator<string, array{Hex, Hex, int}>
	 */
	public static function provideDistances(): \Generator
	{
		yield 'same cell' => [new Hex(2, 3), new Hex(2, 3), 0];
		yield 'neighbour' => [Hex::origin(), new Hex(1, -1), 1];
		yield 'straight line' => [Hex::origin(), new Hex(0, 4), 4];
		yield 'diagonal' => [new Hex(-2, 1), new Hex(3, -1), 5];
	}

	public function testNeighboursAreAllAtDistanceOne(): void
	{
		$center = new Hex(4, -2);
		$neighbours = $center->neighbours();

		static::assertCount(6, $neighbours);
		foreach ($neighbours as $neighbour) {
			static::assertSame(1, $center->distanceTo($neighbour));
		}
	}

	public function testRotationFollowsDirectionsClockwise(): void
	{
		foreach (HexDirection::cases() as $direction) {
			static::assertTrue(
				$direction->toHex()->rotate(1)->equals($direction->rotate(1)->toHex()),
				sprintf('Rotating %s by 60° must give %s', $direction->name, $direction->rotate(1)->name),
			);
		}
	}

	public function testFullRotationIsIdentity(): void
	{
		$hex = new Hex(3, -1);

		static::assertTrue($hex->rotate(6)->equals($hex));
		static::assertTrue($hex->rotate(-1)->rotate(1)->equals($hex));
		static::assertTrue($hex->rotate(3)->equals($hex->scale(-1)));
	}

	public function testRotationKeepsDistanceToCenter(): void
	{
		$center = new Hex(2, 2);
		$hex = new Hex(5, 0);

		for ($sixths = 0; $sixths < 6; ++$sixths) {
			static::assertSame(
				$center->distanceTo($hex),
				$center->distanceTo($hex->rotateAround($center, $sixths)),
			);
		}
	}

	#[DataProvider('provideRanges')]
	public function testRangeContainsEveryCellWithinRadius(int $radius, int $expectedCount): void
	{
		$center = new Hex(-1, 3);
		$cells = $center->range($radius);

		static::assertCount($expectedCount, $cells);
		static::assertCount($expectedCount, array_unique(array_map(fn (Hex $hex) => $hex->toKey(), $cells)));
		foreach ($cells as $cell) {
			static::assertLessThanOrEqual($radius, $center->distanceTo($cell));
		}
	}

	/**
	 * @return \Generator<string, array{int, int}>
	 */
	public static function provideRanges(): \Generator
	{
		yield 'radius 0' => [0, 1];
		yield 'radius 1' => [1, 7];
		yield 'radius 2' => [2, 19];
		yield 'radius 4' => [4, 61];
	}

	public function testOppositeDirection(): void
	{
		static::assertSame(HexDirection::West, HexDirection::East->opposite());
		static::assertSame(HexDirection::NorthEast, HexDirection::SouthWest->opposite());
	}
}
