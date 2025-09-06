<?php

namespace App\Tests\Modules\Shared\Application\Service;

use App\Modules\Shared\Application\Service\GetProportion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GetProportionTest extends TestCase
{
	#[DataProvider('provideData')]
	public function test(array $params, int $cursor, int $expectedResult, bool $expectException = false): void
	{
		$getProportion = new GetProportion();

		if ($expectException) {
			static::expectException(\LogicException::class);
		}
		$result = $getProportion($params, $cursor);

		static::assertSame($expectedResult, $result);
	}

	public function provideData(): \Generator
	{
		yield [
			[0, 60, 0, 10, 30, 0],
			65,
			4,
		];

		yield [
			[0, 60, 0, 10, 30, 0],
			50,
			2,
		];

		yield [
			[50, 10, 0, 10, 30, 0],
			50,
			1,
		];

		yield [
			[50, 0, 0, 10, 30, 10],
			100,
			6,
		];

		yield [
			[50, 0, 0, 10, 30, 20],
			110,
			6,
			true,
		];

		yield [
			[0, 0, 0, 0, 0, 0],
			60,
			6,
		];
	}
}
