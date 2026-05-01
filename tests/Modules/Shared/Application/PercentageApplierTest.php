<?php

namespace App\Tests\Modules\Shared\Application;

use App\Modules\Shared\Application\PercentageApplier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PercentageApplierTest extends TestCase
{
	#[DataProvider('provideIntData')]
	public function testToInt(int|float $value, int|float $percent, int $expectedResult): void
	{
		static::assertSame($expectedResult, PercentageApplier::toInt($value, $percent));
	}

	#[DataProvider('provideFloatData')]
	public function testToFloat(int|float $value, int|float $percent, float $expectedResult): void
	{
		static::assertSame($expectedResult, PercentageApplier::toFloat($value, $percent));
	}

	public static function provideIntData(): \Generator
	{
		yield [
			100.00,
			25,
			25,
		];

		yield [
			250,
			50.00,
			125,
		];

		yield [
			7,
			75,
			5,
		];

		yield [
			0.00,
			10,
			0,
		];

		yield [
			10,
			0,
			0,
		];
	}

	public static function provideFloatData(): \Generator
	{
		yield [
			100.00,
			25,
			25.00,
		];

		yield [
			250,
			50.00,
			125.00,
		];

		yield [
			7,
			75,
			5.25,
		];

		yield [
			0.00,
			10,
			0.00,
		];

		yield [
			10,
			0,
			0,
		];
	}
}
