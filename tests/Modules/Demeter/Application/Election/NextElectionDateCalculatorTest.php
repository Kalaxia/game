<?php

namespace App\Tests\Modules\Demeter\Application\Election;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election\ElectionFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Shared\Domain\Server\TimeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Clock\MockClock;

class NextElectionDateCalculatorTest extends KernelTestCase
{
	public static function setUpBeforeClass(): void
	{
		static::bootKernel();
	}

	public function testGetNextElectionDate(): void
	{
		self::markTestIncomplete('Not Implemented');
	}

	#[DataProvider('provideData')]
	public function testGetCampaignStartDate(array $data, array $expected, TimeMode $timeMode): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode->value;
		$nextElectionDateCalculator = static::getContainer()->get(NextElectionDateCalculator::class);

		$faction = FactionFactory::createOne([
			'identifier' => $data['identifier'],
			'regime' => $data['regime'],
			'electionStatement' => $data['electionStatement'],
			'lastElectionHeldAt' => $data['lastElectionHeldAt'],
		])->object();
		ElectionFactory::createOne([
			'faction' => $faction,
			'dElection' => $data['lastElectionHeldAt'],
		])->object();

		$startDate = $nextElectionDateCalculator->getCampaignStartDate($faction);

		static::assertEquals($expected['campaign_start_date'], $startDate);
	}

	#[DataProvider('provideData')]
	public function testGetPutschEndDate(array $data, array $expected, TimeMode $timeMode): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode->value;
		$nextElectionDateCalculator = static::getContainer()->get(NextElectionDateCalculator::class);

		$faction = FactionFactory::createOne([
			'identifier' => $data['identifier'],
			'regime' => $data['regime'],
			'electionStatement' => $data['electionStatement'],
			'lastElectionHeldAt' => $data['lastElectionHeldAt'],
		])->object();
		ElectionFactory::createOne([
			'faction' => $faction,
			'dElection' => $data['lastElectionHeldAt'],
		])->object();

		$campaignEndDate = $nextElectionDateCalculator->getCampaignEndDate($faction);

		static::assertEquals($expected['putsch_end_date'], $campaignEndDate);
	}

	public function testGetEndDate(): void
	{
		self::markTestIncomplete('Not Implemented');
	}

	public function testGetStartDate(): void
	{
		self::markTestIncomplete('Not Implemented');
	}

	public function testGetBallotDate(): void
	{
		self::markTestIncomplete('Not Implemented');
	}

	public function testGetSenateUpdateMessage(): void
	{
		self::markTestIncomplete('Not Implemented');
	}

	public function testGetCampaignEndDate(): void
	{
		self::markTestIncomplete('Not Implemented');
	}

	/**
	 * @return \Generator<list<array<string, mixed>>>
	 */
	public static function provideData(): \Generator
	{
		Clock::set(new MockClock('2023-06-05 10:00:00'));

		yield 'Standard mode' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'electionStatement' => Color::MANDATE,
				'lastElectionHeldAt' => new DatePoint('2023-06-01 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-08 17:00:00'),
				'putsch_end_date' => new DatePoint('2023-06-12 17:00:00'),
			],
			TimeMode::Standard,
		];

		yield 'Standard mode with late elections' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'electionStatement' => Color::MANDATE,
				'lastElectionHeldAt' => new DatePoint('2023-03-28 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-06 17:00:00'),
				'putsch_end_date' => new DatePoint('2023-06-13 17:00:00'),
			],
			TimeMode::Standard,
		];

		yield 'Fast mode with late elections' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'electionStatement' => Color::MANDATE,
				'lastElectionHeldAt' => new DatePoint('2023-06-04 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-05 10:38:24'),
				'putsch_end_date' => new DatePoint('2023-06-05 10:32:36'),
			],
			TimeMode::Fast,
		];
	}
}
