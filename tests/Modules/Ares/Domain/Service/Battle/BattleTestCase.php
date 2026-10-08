<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Application\Battle\BattleShipTypeRegistry;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleShipType;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\Grid\ClusteredHexGrid;
use App\Modules\Ares\Domain\Service\Battle\BuildFleetFormation;
use App\Modules\Ares\Infrastructure\DependencyInjection\BattleShipTypeConfiguration;
use App\Modules\Ares\Infrastructure\DependencyInjection\ShipCategoryConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds battles with the ship types of the project configuration.
 */
abstract class BattleTestCase extends TestCase
{
	private static ?BattleShipTypeRegistry $registry = null;

	protected static function shipType(string $identifier): BattleShipType
	{
		return self::getRegistry()->get($identifier);
	}

	/**
	 * @param array<string, int> $ships quantities by battle ship type identifier
	 */
	protected static function fleet(string $identifier, int $side, array $ships, int $arrivalTurn = 1, int $officerLevel = 1): BattleFleet
	{
		$formation = (new BuildFleetFormation())(array_map(
			fn (string $type, int $quantity) => [self::shipType($type), $quantity],
			array_keys($ships),
			array_values($ships),
		));

		return new BattleFleet(
			identifier: $identifier,
			name: $identifier,
			faction: $side + 1,
			side: $side,
			officerLevel: $officerLevel,
			formation: $formation,
			arrivalTurn: $arrivalTurn,
		);
	}

	/**
	 * @param list<BattleFleet> $fleets
	 */
	protected static function battle(array $fleets, int $seed = 42, float $durabilityFactor = 1.0, ?int $maxActionsPerTurn = null): BattleState
	{
		return new BattleState(
			grid: new ClusteredHexGrid(4),
			battlefieldSize: 5,
			seed: $seed,
			fleets: $fleets,
			durabilityFactor: $durabilityFactor,
			maxActionsPerTurn: $maxActionsPerTurn,
		);
	}

	protected static function getRegistry(): BattleShipTypeRegistry
	{
		if (null === self::$registry) {
			$projectDir = dirname(__DIR__, 6);
			$processor = new Processor();

			self::$registry = new BattleShipTypeRegistry(
				$processor->processConfiguration(
					new BattleShipTypeConfiguration(),
					[Yaml::parseFile($projectDir.'/config/game/battle_ship_types.yaml', Yaml::PARSE_CONSTANT)['battle_ship_types']],
				),
				$processor->processConfiguration(
					new ShipCategoryConfiguration(),
					[Yaml::parseFile($projectDir.'/config/game/ship_categories.yaml', Yaml::PARSE_CONSTANT)['ship_categories']],
				),
			);
		}

		return self::$registry;
	}
}
