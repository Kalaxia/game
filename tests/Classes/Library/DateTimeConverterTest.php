<?php

declare(strict_types=1);

namespace App\Tests\Classes\Library;

use App\Classes\Library\DateTimeConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateTimeConverterTest extends TestCase
{
	#[DataProvider('provideMsData')]
	public function testConversionToMs(string $dateTime, int $expectedMs): void
	{
		$this->assertEquals($expectedMs, DateTimeConverter::to_ms(new \DateTimeImmutable($dateTime)));
	}

	public static function provideMsData(): \Generator
	{
		yield [
			'+1 minute',
			60 * 1000,
		];

		yield [
			'+1 hour',
			60 * 60 * 1000,
		];

		yield [
			'-6 days',
			0,
		];

		yield [
			'+30 days',
			30 * 24 * 60 * 60 * 1000,
		];
	}
}
