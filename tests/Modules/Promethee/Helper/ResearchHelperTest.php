<?php

namespace App\Tests\Modules\Promethee\Helper;

use App\Modules\Promethee\Helper\ResearchHelper;
use App\Modules\Promethee\Model\Research;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResearchHelperTest extends TestCase
{
	#[DataProvider('provideData')]
	public function testGetResearchPrice(int $research, int $level, float $researchCoeff, int $expectedPrice): void
	{
		$researchHelper = new ResearchHelper($researchCoeff, 1);

		static::assertSame($expectedPrice, $researchHelper->getInfo($research, 'level', $level, 'price'));
	}

	public static function provideData(): \Generator
	{
		yield [Research::MATH, 1, 1, 100];
		yield [Research::PHYS, 1, 1, 3000];
		yield [Research::PHYS, 1, 0.5, 1500];
		yield [Research::CHEM, 1, 1, 7000];
		yield [Research::LAW, 1, 1, 200];
		yield [Research::LAW, 1, 2, 400];
		yield [Research::COMM, 1, 1, 9000];
		yield [Research::ECONO, 1, 1, 200];
		yield [Research::PSYCHO, 1, 1, 9000];
		yield [Research::NETWORK, 1, 1, 200];
		yield [Research::ALGO, 1, 1, 4000];
		yield [Research::STAT, 1, 1, 6000];
		yield [Research::CHEM, 2, 1, 12582];
		yield [Research::STAT, 2, 1, 12582];
		yield [Research::ALGO, 10, 1, 142514];
	}
}
