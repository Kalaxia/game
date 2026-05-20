<?php

namespace App\Tests\Integration\Modules\Zeus\Application\Handler;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Application\Handler\ShipsWageHandler;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use App\Tests\Support\DataFixtures\Foundry\Story\ShipsWageStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(ShipsWageStory::class)]
class ShipsWageHandlerTest extends KernelTestCase
{
	private CommanderRepositoryInterface $commanderRepository;
	private PlayerRepositoryInterface $playerRepository;
	private PlanetRepositoryInterface $planetRepository;
	private ShipsWageHandler $shipsWageHandler;
	private NotificationRepositoryInterface $notificationRepository;

	protected function setUp(): void
	{
		static::bootKernel();

		$this->commanderRepository = static::getContainer()->get(CommanderRepositoryInterface::class);
		$this->playerRepository = static::getContainer()->get(PlayerRepositoryInterface::class);
		$this->planetRepository = static::getContainer()->get(PlanetRepositoryInterface::class);
		$this->notificationRepository = static::getContainer()->get(NotificationRepositoryInterface::class);
		$this->shipsWageHandler = static::getContainer()->get(ShipsWageHandler::class);
	}

	public function testSuccessfulCase(): void
	{
		static::getContainer()->get(EntityManagerInterface::class)->clear();

		$player = $this->playerRepository->get(2);
		$player->credit = 100000;
		$playerFinancialReport = new PlayerFinancialReport(
			id: Uuid::v4(),
			player: $player,
			createdAt: new \DateTimeImmutable(),
			initialWallet: $player->credit,
		);

		$this->shipsWageHandler->payWages(
			$playerFinancialReport,
			$this->commanderRepository->getPlayerCommanders($player),
			$this->planetRepository->getPlayerPlanets($player),
			$this->playerRepository->get(1),
		);

		static::assertSame(100000, $playerFinancialReport->initialWallet);
		static::assertSame(13400, $playerFinancialReport->shipsCost);
	}

	public function testUnaffordableCase(): void
	{
		static::getContainer()->get(EntityManagerInterface::class)->clear();

		$player = $this->playerRepository->get(2);
		$player->credit = 5000;
		$playerFinancialReport = new PlayerFinancialReport(
			id: Uuid::v4(),
			player: $player,
			createdAt: new \DateTimeImmutable(),
			initialWallet: $player->credit,
		);

		$commanders = $this->commanderRepository->getPlayerCommanders($player, orderBy: ['c.name' => 'ASC']);

		static::assertCount(2, $commanders);

		$this->shipsWageHandler->payWages(
			$playerFinancialReport,
			$commanders,
			$this->planetRepository->getPlayerPlanets($player),
			$this->playerRepository->get(1),
		);

		static::assertSame(5000, $playerFinancialReport->initialWallet);
		static::assertSame(3900, $playerFinancialReport->shipsCost);

		$notifications = $this->notificationRepository->getPlayerNotificationsByArchive($player, false);

		static::assertCount(1, $notifications);
		static::assertSame('Flotte impayée', $notifications[0]->title);

		$commanders = $this->commanderRepository->getPlayerCommanders($player, orderBy: ['c.name' => 'ASC']);

		static::assertCount(1, $commanders);
	}
}
