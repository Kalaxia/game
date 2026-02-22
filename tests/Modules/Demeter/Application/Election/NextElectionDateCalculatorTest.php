<?php

namespace App\Tests\Modules\Demeter\Application\Election;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election\ElectionFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Shared\Domain\Server\TimeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Clock\MockClock;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class NextElectionDateCalculatorTest extends KernelTestCase
{
	use ResetDatabase;
	use Factories;

	public static function setUpBeforeClass(): void
	{
		static::bootKernel();
	}

	public function testGetNextElectionDate(): void
	{
		self::markTestIncomplete('Not Implemented');
	}

	#[DataProvider('provideDemocracyData')]
	public function testDemocracyWorkflow(array $data, array $expected, TimeMode $timeMode): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode->value;
		/** @var NextElectionDateCalculator $nextElectionDateCalculator */
		$nextElectionDateCalculator = static::getContainer()->get(NextElectionDateCalculator::class);

		$faction = FactionFactory::createOne([
			'identifier' => $data['identifier'],
			'regime' => $data['regime'],
			'mandateState' => $data['mandateState'],
			'lastElectionHeldAt' => $data['lastElectionHeldAt'],
		]);
		ElectionFactory::createOne([
			'faction' => $faction,
			'dElection' => $data['lastElectionHeldAt'],
		]);

		static::assertEquals(
			$expected['campaign_start_date'],
			$nextElectionDateCalculator->getDateUntil($faction, MandateState::DemocraticCampaign),
			'Wrong campaign start date',
		);
		static::assertEquals(
			$expected['campaign_end_date'],
			$nextElectionDateCalculator->getDateUntil($faction, MandateState::DemocraticVote),
			'Wrong campaign end date',
		);
		static::assertEquals(
			$expected['election_end_date'],
			$nextElectionDateCalculator->getDateUntil($faction, MandateState::Active),
			'Wrong election end date',
		);
		static::assertEquals(
			$expected['mandate_duration'],
			$nextElectionDateCalculator->getMandateDuration($faction),
			'Wrong mandate duration',
		);
	}

	#[DataProvider('provideMonarchyData')]
	public function testGetPutschEndDate(array $data, array $expected, TimeMode $timeMode): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode->value;
		/** @var NextElectionDateCalculator $nextElectionDateCalculator */
		$nextElectionDateCalculator = static::getContainer()->get(NextElectionDateCalculator::class);

		$faction = FactionFactory::createOne([
			'identifier' => $data['identifier'],
			'regime' => $data['regime'],
			'mandateState' => $data['mandateState'],
			'lastElectionHeldAt' => $data['lastElectionHeldAt'],
		]);
		ElectionFactory::createOne([
			'faction' => $faction,
			'dElection' => $data['lastElectionHeldAt'],
		]);

		$campaignEndDate = $nextElectionDateCalculator->getDateUntil($faction, MandateState::Active);

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

	/**
	 * @return \Generator<list<array<string, mixed>>>
	 */
	public static function provideDemocracyData(): \Generator
	{
		Clock::set(new MockClock('2023-06-05 10:00:00'));

		yield 'Standard mode democracy during mandate' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-06-01 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-08 17:00:00'),
				'campaign_end_date' => new DatePoint('2023-06-12 17:00:00'),
				'election_end_date' => new DatePoint('2023-06-14 17:00:00'),
				'mandate_duration' => 604800,
			],
			TimeMode::Standard,
		];

		yield 'Standard mode democracy during campaign' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-06-01 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-08 17:00:00'),
				'campaign_end_date' => new DatePoint('2023-06-12 17:00:00'),
				'election_end_date' => new DatePoint('2023-06-14 17:00:00'),
				'mandate_duration' => 604800,
			],
			TimeMode::Standard,
		];

		yield 'Standard mode democracy with late elections' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-03-28 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-06 17:00:00'),
				'campaign_end_date' => new DatePoint('2023-06-12 17:00:00'),
				'election_end_date' => new DatePoint('2023-06-14 17:00:00'),
				'mandate_duration' => 604800,
			],
			TimeMode::Standard,
		];

		yield 'Fast mode democracy with late elections' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-06-04 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-05 10:20:00'),
				'campaign_end_date' => new DatePoint('2023-06-08 17:00:00'),
				'election_end_date' => new DatePoint('2023-06-12 17:00:00'),
				'mandate_duration' => 2400,
			],
			TimeMode::Fast,
		];
	}

	/**
	 * @return \Generator<list<array<string, mixed>>>
	 */
	public static function provideMonarchyData(): \Generator
	{
		Clock::set(new MockClock('2023-06-05 10:00:00'));

		yield 'Standard mode democracy during mandate' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-06-01 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-08 17:00:00'),
				'putsch_end_date' => new DatePoint('2023-06-12 17:00:00'),
			],
			TimeMode::Standard,
		];

		yield 'Standard mode theocracy during campaign' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_DEMOCRATIC,
				'mandateState' => MandateState::Active,
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
				'mandateState' => MandateState::Active,
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
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-06-04 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-05 10:20:00'),
				'putsch_end_date' => new DatePoint('2023-06-05 10:15:00'),
			],
			TimeMode::Fast,
		];
	}

	/**
	 * @return \Generator<list<array<string, mixed>>>
	 */
	public static function provideTheocracyData(): \Generator
	{
		Clock::set(new MockClock('2023-06-05 10:00:00'));

		yield 'Standard mode theocracy during mandate' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_THEOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-06-01 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-08 17:00:00'),
				'campaign_end_date' => new DatePoint('2023-06-12 17:00:00'),
				'mandate_duration' => 3600,
			],
			TimeMode::Standard,
		];

		yield 'Standard mode theocracy during campaign' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_THEOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-06-01 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-08 17:00:00'),
				'campaign_end_date' => new DatePoint('2023-06-12 17:00:00'),
				'mandate_duration' => 3600,
			],
			TimeMode::Standard,
		];

		yield 'Standard mode theocracy with late elections' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_THEOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-03-28 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-06 17:00:00'),
				'campaign_end_date' => new DatePoint('2023-06-12 17:00:00'),
				'mandate_duration' => 3600,
			],
			TimeMode::Standard,
		];

		yield 'Fast mode theocracy with late elections' => [
			[
				'identifier' => ColorResource::KALANKAR,
				'regime' => Color::REGIME_THEOCRATIC,
				'mandateState' => MandateState::Active,
				'lastElectionHeldAt' => new DatePoint('2023-06-04 17:00:00'),
			],
			[
				'campaign_start_date' => new DatePoint('2023-06-05 10:20:00'),
				'campaign_end_date' => new DatePoint('2023-06-12 17:00:00'),
				'mandate_duration' => 3600,
			],
			TimeMode::Fast,
		];
	}
}
