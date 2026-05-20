<?php

declare(strict_types=1);

namespace App\Tests\Integration\Modules\Ares\Application\Handler;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Ship;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Tests\Support\DataFixtures\Foundry\Story\CommanderArmyStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(CommanderArmyStory::class)]
class CommanderArmyHandlerTest extends KernelTestCase
{
	public function testInitializeShips(): void
	{
		static::bootKernel();

		/** @var CommanderArmyHandler $commanderArmyHandler */
		$commanderArmyHandler = static::getContainer()->get(CommanderArmyHandler::class);
		/** @var PlayerRepositoryInterface $playerRepository */
		$playerRepository = static::getContainer()->get(PlayerRepositoryInterface::class);
		/** @var CommanderRepositoryInterface $commanderRepository */
		$commanderRepository = static::getContainer()->get(CommanderRepositoryInterface::class);

		$player = $playerRepository->get(1);
		$commander = $commanderRepository->getPlayerCommanders($player)[0];

		$commanderArmyHandler->setArmy($commander);

		static::assertIsArray($commander->army, 'The commander army should be an array');
		static::assertCount(8, $commander->army, 'The commander army should have 6 squadrons');

		foreach ($commander->army as $index => $squadron) {
			static::assertTrue($squadron->areShipsInitialized, 'Squadron ships should be initialized');
			static::assertIsArray($squadron->ships, 'The army ships should be an array');

			if ($index < 5) {
				static::assertCount(10, $squadron->ships);

				foreach ($squadron->ships as $j => $ship) {
					static::assertInstanceOf(Ship::class, $ship);
					static::assertSame(
						(($j < 9) ? ShipCategory::Fighter : ShipCategory::Cruiser)->value,
						$ship->shipNumber,
					);
				}
			} elseif (6 === $index) {
				static::assertCount(40, $squadron->ships);

				foreach ($squadron->ships as $j => $ship) {
					static::assertInstanceOf(Ship::class, $ship);
					static::assertSame(
						(($j < 25) ? ShipCategory::LightFighter : ShipCategory::Fighter)->value,
						$ship->shipNumber,
					);
				}
			} else {
				static::assertCount(0, $squadron->ships);
			}
		}
	}
}
