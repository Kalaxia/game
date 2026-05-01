<?php

declare(strict_types=1);

namespace App\Tests\Functional\Modules\Demeter;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Handler\CampaignHandler;
use App\Modules\Demeter\Handler\ElectionHandler;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election\CandidateFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election\DemocraticElectionFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election\MandateFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Story\DemocraticRegimeWorkflowStory;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Message\MandateExpirationMessage;
use App\Modules\Demeter\Model\Election\DemocraticElection;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use App\Modules\Zeus\Infrastructure\Validator\IsParliamentMember;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DemocraticRegimeWorkflowTest extends KernelTestCase
{
	use Factories;
	use ResetDatabase;

	public DatePoint $datePoint;

	public function setUp(): void
	{
		$this->datePoint = new DatePoint();
		Clock::set(new MockClock($this->datePoint));

		$_ENV['SERVER_TIME_MODE'] = 'standard';
		$_ENV['SERVER_START_TIME'] = (new \DateTimeImmutable('-2 hours'))->format('Y-m-d H:i:s');

		static::bootKernel();

		DemocraticRegimeWorkflowStory::load();
	}

	public function testCampaignWorkflow(): void
	{
		/**************** SETUP ****************/
		/** @var ColorRepositoryInterface $factionRepository */
		$factionRepository = static::getContainer()->get(ColorRepositoryInterface::class);

		$faction = $factionRepository->getOneByIdentifier(ColorResource::KALANKAR)
			?? throw new \RuntimeException('Faction 1 has not been properly initialized in the fixtures');

		/**************** EXECUTE CAMPAIGN WORKFLOW ****************/

		/** @var CampaignHandler $campaignHandler */
		$campaignHandler = static::getContainer()->get(CampaignHandler::class);

		$campaignHandler(new CampaignMessage($faction->id, new DatePoint()));

		/**************** VALIDATE MANDATE STATE CHANGE ****************/

		static::assertSame(MandateState::DemocraticCampaign, $faction->mandateState);

		/**************** VALIDATE SENATE UPDATE ****************/

		/** @var PlayerRepositoryInterface $playerRepository */
		$playerRepository = static::getContainer()->get(PlayerRepositoryInterface::class);
		$senators = $playerRepository->getBySpecification(new IsParliamentMember($faction));

		static::assertCount(10, $senators);

		/**************** VALIDATE POLITICAL EVENT CREATION ****************/

		/** @var PoliticalEventRepositoryInterface $politicalEventRepository */
		$politicalEventRepository = static::getContainer()->get(PoliticalEventRepositoryInterface::class);
		$democraticElection = $politicalEventRepository->getFactionCurrentPoliticalEvent($faction);

		static::assertNotNull($democraticElection);
		static::assertInstanceOf(DemocraticElection::class, $democraticElection);
		static::assertEquals($democraticElection->startedAt, $this->datePoint);
		static::assertEquals(
			$democraticElection->campaignEndedAt,
			$this->datePoint->add(\DateInterval::createFromDateString('4 days')),
		);
		static::assertEquals(
			$democraticElection->endedAt,
			$this->datePoint->add(\DateInterval::createFromDateString('6 days')),
		);

		/**************** VALIDATE MESSAGES DISPATCH ****************/

		/** @var InMemoryTransport $asyncTransport */
		$asyncTransport = static::getContainer()->get('messenger.transport.async');
		$sentMessages = $asyncTransport->getSent();
		static::assertCount(2, $sentMessages);

		$this->validateMessage(
			envelope: $sentMessages[0],
			expectedMessageClass: ElectionMessage::class,
			delay: '4 days',
		);

		$this->validateMessage(
			envelope: $sentMessages[1],
			expectedMessageClass: CampaignMessage::class,
			delay: '7 days',
		);

		/***************** VALIDATE MANDATE ABSENCE AT THIS POINT ****************/

		/** @var MandateRepositoryInterface $mandateRepository */
		$mandateRepository = static::getContainer()->get(MandateRepositoryInterface::class);

		static::assertCount(0, $mandateRepository->getFactionMandates($faction));
	}

	public function testCampaignWorkflowAfterMandateGap(): void
	{
		/**************** SETUP ****************/
		/** @var ColorRepositoryInterface $factionRepository */
		$factionRepository = static::getContainer()->get(ColorRepositoryInterface::class);

		$faction = $factionRepository->getOneByIdentifier(ColorResource::KALANKAR)
			?? throw new \RuntimeException('Faction 1 has not been properly initialized in the fixtures');

		DemocraticElectionFactory::createOne([
			'faction' => $faction,
			'startedAt' => $this->datePoint->sub(\DateInterval::createFromDateString('35 days')),
			'campaignEndedAt' => $this->datePoint->sub(\DateInterval::createFromDateString('31 days')),
			'endedAt' => $this->datePoint->sub(\DateInterval::createFromDateString('29 days')),
		]);

		MandateFactory::createOne([
			'faction' => $faction,
			'startedAt' => $this->datePoint->sub(\DateInterval::createFromDateString('28 days')),
			'expiredAt' => $this->datePoint->sub(\DateInterval::createFromDateString('21 days')),
			'leader' => null,
		]);

		/**************** EXECUTE CAMPAIGN WORKFLOW ****************/

		/** @var CampaignHandler $campaignHandler */
		$campaignHandler = static::getContainer()->get(CampaignHandler::class);

		$campaignHandler(new CampaignMessage($faction->id, new DatePoint()));

		/**************** VALIDATE MANDATE STATE CHANGE ****************/

		static::assertSame(MandateState::DemocraticCampaign, $faction->mandateState);

		/**************** VALIDATE POLITICAL EVENT CREATION ****************/

		/** @var PoliticalEventRepositoryInterface $politicalEventRepository */
		$politicalEventRepository = static::getContainer()->get(PoliticalEventRepositoryInterface::class);
		$democraticElection = $politicalEventRepository->getFactionCurrentPoliticalEvent($faction);

		static::assertNotNull($democraticElection);
		static::assertInstanceOf(DemocraticElection::class, $democraticElection);
		static::assertEquals($democraticElection->startedAt, $this->datePoint);
		static::assertEquals(
			$democraticElection->campaignEndedAt,
			$this->datePoint->add(\DateInterval::createFromDateString('4 days')),
		);
		static::assertEquals(
			$democraticElection->endedAt,
			$this->datePoint->add(\DateInterval::createFromDateString('6 days')),
		);

		/**************** VALIDATE MESSAGES DISPATCH ****************/

		/** @var InMemoryTransport $asyncTransport */
		$asyncTransport = static::getContainer()->get('messenger.transport.async');
		$sentMessages = $asyncTransport->getSent();
		static::assertCount(2, $sentMessages);

		$this->validateMessage(
			envelope: $sentMessages[0],
			expectedMessageClass: ElectionMessage::class,
			delay: '4 days',
		);

		$this->validateMessage(
			envelope: $sentMessages[1],
			expectedMessageClass: CampaignMessage::class,
			delay: '7 days',
		);
	}

	public function testElectionWorkflowWithoutCandidates(): void
	{
		/**************** SETUP ****************/
		/** @var ColorRepositoryInterface $factionRepository */
		$factionRepository = static::getContainer()->get(ColorRepositoryInterface::class);

		$faction = $factionRepository->getOneByIdentifier(ColorResource::KALANKAR)
			?? throw new \RuntimeException('Faction 1 has not been properly initialized in the fixtures');
		$faction->mandateState = MandateState::DemocraticCampaign;

		DemocraticElectionFactory::createOne([
			'faction' => $faction,
			'startedAt' => $this->datePoint,
			'campaignEndedAt' => $this->datePoint->add(\DateInterval::createFromDateString('4 days')),
			'endedAt' => $this->datePoint->add(\DateInterval::createFromDateString('6 days')),
		]);

		/**************** EXECUTE ELECTION WORKFLOW ****************/

		/** @var ElectionHandler $electionHandler */
		$electionHandler = static::getContainer()->get(ElectionHandler::class);

		$electionHandler(new ElectionMessage($faction->id));

		/**************** VALIDATE MANDATE STATE CHANGE ****************/

		static::assertSame(MandateState::Active, $faction->mandateState);

		/**************** VALIDATE MANDATE ABSENCE ****************/

		/** @var MandateRepositoryInterface $mandateRepository */
		$mandateRepository = static::getContainer()->get(MandateRepositoryInterface::class);
		$mandate = $mandateRepository->getCurrentMandate($faction);

		static::assertNotNull($mandate);
		static::assertNull($mandate->leader);
		static::assertEquals($this->datePoint, $mandate->startedAt);
		static::assertEquals(
			$this->datePoint->add(\DateInterval::createFromDateString('7 days')),
			$mandate->expiredAt,
		);

		/**************** VALIDATE MESSAGES DISPATCH ****************/

		/** @var InMemoryTransport $asyncTransport */
		$asyncTransport = static::getContainer()->get('messenger.transport.async_high_priority');
		$sentMessages = $asyncTransport->getSent();
		static::assertCount(1, $sentMessages);

		$this->validateMessage(
			envelope: $sentMessages[0],
			expectedMessageClass: MandateExpirationMessage::class,
			delay: '7 days',
			delta: 10,
		);
	}

	public function testElectionWorkflowWithUniqueCandidate(): void
	{
		/**************** SETUP ****************/
		/** @var ColorRepositoryInterface $factionRepository */
		$factionRepository = static::getContainer()->get(ColorRepositoryInterface::class);
		/** @var PlayerRepositoryInterface $playerRepository */
		$playerRepository = static::getContainer()->get(PlayerRepositoryInterface::class);

		$faction = $factionRepository->getOneByIdentifier(ColorResource::KALANKAR)
			?? throw new \RuntimeException('Faction 1 has not been properly initialized in the fixtures');
		$faction->mandateState = MandateState::DemocraticCampaign;

		$democraticElection = DemocraticElectionFactory::createOne([
			'faction' => $faction,
			'startedAt' => $this->datePoint,
			'campaignEndedAt' => $this->datePoint->add(\DateInterval::createFromDateString('4 days')),
			'endedAt' => $this->datePoint->add(\DateInterval::createFromDateString('6 days')),
		]);

		$candidatePlayer = $playerRepository->getBySpecification(new IsParliamentMember($faction))[0]
			?? throw new \RuntimeException('No senator found for faction 1');
		CandidateFactory::createOne([
			'player' => $candidatePlayer,
			'politicalEvent' => $democraticElection,
		]);

		/**************** EXECUTE ELECTION WORKFLOW ****************/

		/** @var ElectionHandler $electionHandler */
		$electionHandler = static::getContainer()->get(ElectionHandler::class);

		$electionHandler(new ElectionMessage($faction->id));

		/**************** VALIDATE MANDATE STATE CHANGE ****************/

		static::assertSame(MandateState::Active, $faction->mandateState);

		/**************** VALIDATE PLAYER STATUS CHANGE ****************/

		static::assertEquals(Player::CHIEF, $candidatePlayer->status);

		/**************** VALIDATE MANDATE CREATION ****************/

		/** @var MandateRepositoryInterface $mandateRepository */
		$mandateRepository = static::getContainer()->get(MandateRepositoryInterface::class);
		$mandate = $mandateRepository->getCurrentMandate($faction);

		static::assertNotNull($mandate);
		static::assertEquals($candidatePlayer, $mandate->leader);
		static::assertEquals($this->datePoint, $mandate->startedAt);
		static::assertEquals(
			$this->datePoint->add(\DateInterval::createFromDateString('7 days')),
			$mandate->expiredAt,
		);

		/**************** VALIDATE MESSAGES DISPATCH ****************/

		/** @var InMemoryTransport $asyncTransport */
		$asyncTransport = static::getContainer()->get('messenger.transport.async_high_priority');
		$sentMessages = $asyncTransport->getSent();
		static::assertCount(1, $sentMessages);

		$this->validateMessage(
			envelope: $sentMessages[0],
			expectedMessageClass: MandateExpirationMessage::class,
			delay: '7 days',
		);
	}

	private function validateMessage(
		Envelope $envelope,
		string $expectedMessageClass,
		string $delay,
		int $delta = 10,
	): void {
		$delayStamp = $envelope->last(DelayStamp::class);

		static::assertInstanceOf($expectedMessageClass, $envelope->getMessage());
		static::assertNotNull($delayStamp);
		static::assertInstanceOf(DelayStamp::class, $delayStamp);
		static::assertEqualsWithDelta(
			$delayStamp->getDelay() / 1000,
			$this->datePoint->add(\DateInterval::createFromDateString($delay))->getTimestamp()
			- $this->datePoint->getTimestamp(),
			$delta,
		);
	}
}
