<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Infrastructure\Twig;

use App\Modules\Ares\Application\Battle\GetBattleRules;
use App\Modules\Ares\Application\Battle\GetBattleShipTypeName;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\Battle\BuildFleetFormation;
use App\Modules\Ares\Domain\Service\GetCommanderPev;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Ares\Domain\Service\GetSquadronPev;
use App\Modules\Ares\Infrastructure\DependencyInjection\ShipCategoryConfiguration;
use App\Modules\Ares\Infrastructure\Twig\Components\Organisms\FleetFormation;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Tests\Modules\Ares\Domain\Service\Battle\BattleTestCase;
use App\Tests\Modules\Ares\Support\BuildCommander;
use App\Tests\Modules\Ares\Support\BuildPlanet;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Composition of a fleet with the ships of the hangar (D9).
 */
class FleetFormationTest extends BattleTestCase
{
	public function testAPlacedSquadronTakesItsShipsFromTheHangar(): void
	{
		[$component, $commander] = $this->createComponent([ShipCategory::Corvette->value => 30]);

		$component->selectedCategory = ShipCategory::Corvette->value;
		$component->clickCell(1, 0);
		$component->clickCell(1, -1);

		$ships = array_map(fn (Squadron $squadron) => [$squadron->shipCategory, $squadron->ships, $squadron->q, $squadron->r], $commander->squadrons->toArray());
		// A corvette squadron holds 14 ships at most
		static::assertSame([[ShipCategory::Corvette, 14, 1, 0], [ShipCategory::Corvette, 14, 1, -1]], array_values($ships));
		static::assertSame(2, $commander->base->getShipStorage()[ShipCategory::Corvette->value]);

		$component->clickCell(0, 0);
		static::assertSame(2, $commander->getSquadronAt(0, 0)->ships, 'The last ships of the hangar');
		$component->clickCell(0, 1);
		static::assertNull($commander->getSquadronAt(0, 1));
		static::assertNotNull($component->error);
	}

	public function testRemovingOrReplacingASquadronReturnsItsShips(): void
	{
		[$component, $commander] = $this->createComponent([ShipCategory::Corvette->value => 20, ShipCategory::LightFighter->value => 60]);

		$component->selectedCategory = ShipCategory::Corvette->value;
		$component->clickCell(0, 0);
		$component->selectedCategory = ShipCategory::LightFighter->value;
		$component->clickCell(0, 0);

		static::assertSame(ShipCategory::LightFighter, $commander->getSquadronAt(0, 0)->shipCategory);
		static::assertSame(20, $commander->base->getShipStorage()[ShipCategory::Corvette->value]);

		$component->tool = 'remove';
		$component->clickCell(0, 0);

		static::assertCount(0, $commander->squadrons);
		static::assertSame(60, $commander->base->getShipStorage()[ShipCategory::LightFighter->value]);
	}

	public function testAutomaticArrangementKeepsTheShips(): void
	{
		[$component, $commander] = $this->createComponent([ShipCategory::Corvette->value => 14, ShipCategory::Destroyer->value => 1]);

		$component->selectedCategory = ShipCategory::Corvette->value;
		$component->clickCell(-2, 0);
		$component->selectedCategory = ShipCategory::Destroyer->value;
		$component->clickCell(2, 0);
		$component->autoArrange();

		static::assertSame([0, 0, 0, 0, 14, 0, 0, 0, 1, 0, 0, 0], $commander->getNbrShipByType());
		static::assertCount(2, $commander->squadrons);
		static::assertNull($commander->getSquadronAt(-2, 0), 'The squadrons are moved to the front');
	}

	public function testAFleetAwayFromItsBaseCannotBeComposed(): void
	{
		[$component, $commander] = $this->createComponent([ShipCategory::Corvette->value => 14]);
		$commander->statement = Commander::MOVING;

		static::assertFalse($component->canCompose());
		$this->expectException(AccessDeniedHttpException::class);
		$component->clickCell(0, 0);
	}

	/**
	 * @param array<int, int> $hangar
	 *
	 * @return array{FleetFormation, Commander}
	 */
	private function createComponent(array $hangar): array
	{
		$projectDir = dirname(__DIR__, 5);
		$player = BuildCommander::player(1, 2);
		$base = BuildPlanet::build(player: $player);
		$base->shipStorage = $hangar;
		$commander = BuildCommander::build($player, $base);

		$translator = $this->createStub(TranslatorInterface::class);
		$translator->method('trans')->willReturnArgument(0);
		$parameters = Yaml::parseFile($projectDir.'/src/Modules/Ares/Resource/config/config.yml')['parameters'];
		$shipCategories = (new Processor())->processConfiguration(
			new ShipCategoryConfiguration(),
			[Yaml::parseFile($projectDir.'/config/game/ship_categories.yaml', Yaml::PARSE_CONSTANT)['ship_categories']],
		);
		$currentPlayerRegistry = new CurrentPlayerRegistry();
		$currentPlayerRegistry->set($player);

		$component = new FleetFormation(
			self::getRegistry(),
			new GetBattleShipTypeName($translator),
			new BuildFleetFormation(2),
			new GetBattleRules($parameters['ares.battle.command'], $parameters['ares.battle.engagement'], $parameters['ares.battle.morale'], 10),
			new GetCommanderPev(new GetSquadronPev(new GetShipCategoriesConfiguration($translator, $shipCategories))),
			$this->createStub(CommanderManager::class),
			$this->createStub(CommanderRepositoryInterface::class),
			$currentPlayerRegistry,
			$this->createStub(EventDispatcherInterface::class),
			$this->createStub(EntityManagerInterface::class),
			new MockClock(),
			2,
		);
		$component->commander = $commander;

		return [$component, $commander];
	}
}
