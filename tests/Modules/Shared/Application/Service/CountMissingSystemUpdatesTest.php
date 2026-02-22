<?php

namespace App\Tests\Modules\Shared\Application\Service;

use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Shared\Application\Service\CountMissingSystemUpdates;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;

class CountMissingSystemUpdatesTest extends KernelTestCase
{
	#[DataProvider('provideData')]
	public function test(string $currentDate, string $lastUpdatedAt, string $timeMode, int $expectedMissingUpdatesCount): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode;
		Clock::set(new MockClock($currentDate));
		/** @var CountMissingSystemUpdates $countMissingSystemUpdates */
		$countMissingSystemUpdates = static::getContainer()->get(CountMissingSystemUpdates::class);

		FactionFactory::createOne();
		$player = PlayerFactory::createOne([
			'uPlayer' => new \DateTimeImmutable($lastUpdatedAt),
		])->object();

		static::assertSame($expectedMissingUpdatesCount, $countMissingSystemUpdates($player));
	}

	public static function provideData(): \Generator
	{
		yield [
			'2024-05-01 10:00:00',
			'2024-05-01 09:50:10',
			'fast',
			1,
		];

		yield [
			'2024-05-01 09:53:00',
			'2024-05-01 09:50:10',
			'fast',
			0,
		];

		yield [
			'2024-05-01 10:00:00',
			'2024-05-01 09:40:10',
			'fast',
			2,
		];

		yield [
			'2024-05-01 10:01:00',
			'2024-05-01 09:40:10',
			'fast',
			2,
		];
	}
}
