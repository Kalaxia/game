<?php

namespace App\Tests\Modules\Demeter\Domain\Service\Law;

use App\Modules\Demeter\Domain\Service\Law\GetApplicationDuration;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Shared\Domain\Server\TimeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Clock\MockClock;

class GetApplicationDurationTest extends KernelTestCase
{
	#[DataProvider('provideData')]
	public function test(int $lawType, ?int $cycles, string $expectedEndDate, bool $expectedException = false, TimeMode $timeMode = TimeMode::Standard): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode->value;
		static::bootKernel();

		Clock::set(new MockClock('2024-01-01 10:00:00'));

		if ($expectedException) {
			static::expectException(\InvalidArgumentException::class);
		}

		/** @var GetApplicationDuration $getApplicationDuration */
		$getApplicationDuration = static::getContainer()->get(GetApplicationDuration::class);

		$duration = $getApplicationDuration($lawType, $cycles);

		static::assertEquals(new DatePoint($expectedEndDate), $duration);
	}

	public static function provideData(): \Generator
	{
		yield 'Standard instantaneous law' => [
			Law::COMTAXEXPORT,
			null,
			'2024-01-01 10:00:00',
		];

		yield 'Standard instaneous law with forbidden cycles argument' => [
			Law::COMTAXEXPORT,
			3,
			'2024-01-01 10:00:00',
			true,
		];

		yield 'Standard duration law with missing cycles argument' => [
			Law::MILITARYSUBVENTION,
			null,
			'2024-01-01 10:00:00',
			true,
		];

		yield 'Standard duration law' => [
			Law::MILITARYSUBVENTION,
			5,
			'2024-01-01 15:00:00',
		];

		yield 'Fast duration law' => [
			Law::MILITARYSUBVENTION,
			6,
			'2024-01-01 11:00:00',
			false,
			TimeMode::Fast,
		];

		yield 'Standard duration law with minimum duration' => [
			Law::MILITARYSUBVENTION,
			-4,
			'2024-01-01 11:00:00',
			false,
			TimeMode::Fast,
		];

		yield 'Fast duration law with minimum duration' => [
			Law::MILITARYSUBVENTION,
			-4,
			'2024-01-01 10:10:00',
			false,
			TimeMode::Fast,
		];

		yield 'Standard duration law with exceeding duration' => [
			Law::MILITARYSUBVENTION,
			3000,
			'+100 days',
		];

		yield 'Fast duration law with exceeding duration' => [
			Law::MILITARYSUBVENTION,
			100000,
			'+100 days',
			false,
			TimeMode::Fast,
		];
	}
}
