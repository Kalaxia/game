<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleShipType;
use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Ares\Domain\Model\Battle\ShipRole;
use App\Modules\Ares\Domain\Model\Battle\WeaponPattern;
use App\Modules\Ares\Domain\Model\ShipCategory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Builds the battle ship types from config/game/battle_ship_types.yaml,
 * completed by the base stats of config/game/ship_categories.yaml.
 */
class BattleShipTypeRegistry
{
	/** @var array<string, BattleShipType>|null */
	private ?array $types = null;

	/**
	 * @param array<string, array<string, mixed>> $battleShipTypesConfiguration
	 * @param array<int, array<string, mixed>>    $shipCategoriesConfiguration
	 */
	public function __construct(
		#[Autowire('%app.battle_ship_types%')]
		private readonly array $battleShipTypesConfiguration,
		#[Autowire('%app.ship_categories%')]
		private readonly array $shipCategoriesConfiguration,
	) {
	}

	/**
	 * Battle ship type of the ships built in the game under this category.
	 */
	public function getByCategory(ShipCategory $category): BattleShipType
	{
		return array_find($this->all(), fn (BattleShipType $type) => $type->shipCategory === $category)
			?? throw new \InvalidArgumentException(sprintf('No battle ship type for ship category %s', $category->name));
	}

	public function get(string $identifier): BattleShipType
	{
		return $this->all()[$identifier]
			?? throw new \InvalidArgumentException(sprintf('Unknown battle ship type %s', $identifier));
	}

	/**
	 * @return array<string, BattleShipType>
	 */
	public function all(): array
	{
		return $this->types ??= array_map(
			fn (string $identifier) => $this->build($identifier, $this->battleShipTypesConfiguration[$identifier]),
			array_combine(array_keys($this->battleShipTypesConfiguration), array_keys($this->battleShipTypesConfiguration)),
		);
	}

	/**
	 * @param array<string, mixed> $configuration
	 */
	private function build(string $identifier, array $configuration): BattleShipType
	{
		$shipCategory = isset($configuration['ship_category']) ? ShipCategory::from($configuration['ship_category']) : null;
		// Stats defined in the battle configuration take precedence over the ship category ones.
		// Empty arrays are defaults of the configuration tree, not overrides.
		$overrides = array_filter($configuration, fn (mixed $value) => [] !== $value);
		$stats = $overrides + (null !== $shipCategory ? $this->shipCategoriesConfiguration[$shipCategory->value] : []);

		return new BattleShipType(
			identifier: $identifier,
			shipCategory: $shipCategory,
			class: ShipClass::from($stats['class']),
			role: ShipRole::from($stats['role']),
			pev: $stats['pev'],
			life: $stats['life'],
			defense: $stats['defense'],
			speed: $stats['speed'],
			attack: array_values($stats['attack']),
			squadronSize: $stats['squadron_size'],
			movement: $stats['movement'],
			initiative: $stats['initiative'],
			range: $stats['range'],
			preferredTargets: array_map(fn (int $class) => ShipClass::from($class), $stats['preferred_targets'] ?? []),
			patterns: array_map(fn (string $pattern) => WeaponPattern::from($pattern), $stats['patterns'] ?? [WeaponPattern::Close->value]),
			minRange: $stats['min_range'] ?? 1,
			splashRatio: (float) ($stats['splash_ratio'] ?? 0.0),
			splitFire: $stats['split_fire'] ?? false,
			pictogram: $stats['pictogram'],
		);
	}
}
