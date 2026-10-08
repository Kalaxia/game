<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\DependencyInjection;

use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Ares\Domain\Model\Battle\ShipRole;
use App\Modules\Ares\Domain\Model\Battle\WeaponPattern;
use App\Modules\Ares\Domain\Model\ShipCategory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class BattleShipTypeConfiguration implements ConfigurationInterface
{
	private const array BASE_STATS = ['class', 'pev', 'life', 'defense', 'speed', 'attack'];

	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder('battle_ship_types');

		$treeBuilder->getRootNode()
			->isRequired()
			->requiresAtLeastOneElement()
			->useAttributeAsKey('identifier')
			->arrayPrototype()
				->validate()
					->ifTrue(fn (array $type) => !isset($type['ship_category']) && count(array_intersect_key(array_flip(self::BASE_STATS), array_filter($type, fn (mixed $value) => [] !== $value))) < count(self::BASE_STATS))
					->thenInvalid(sprintf('A battle ship type without ship category must define its base stats (%s)', implode(', ', self::BASE_STATS)))
				->end()
				->children()
					->integerNode('ship_category')->min(0)->max(count(ShipCategory::cases()) - 1)->end()
					->enumNode('role')->isRequired()->values(array_map(fn (ShipRole $role) => $role->value, ShipRole::cases()))->end()
					->enumNode('class')->values(array_map(fn (ShipClass $class) => $class->value, ShipClass::cases()))->end()
					->integerNode('pev')->min(1)->end()
					->integerNode('life')->min(1)->end()
					->integerNode('defense')->min(0)->end()
					->integerNode('speed')->min(0)->end()
					->arrayNode('attack')
						->integerPrototype()->min(0)->end()
					->end()
					->integerNode('squadron_size')->isRequired()->min(1)->end()
					->integerNode('movement')->isRequired()->min(0)->end()
					->integerNode('initiative')->isRequired()->min(0)->end()
					->integerNode('range')->isRequired()->min(1)->end()
					->arrayNode('patterns')
						->enumPrototype()->values(array_map(fn (WeaponPattern $pattern) => $pattern->value, WeaponPattern::cases()))->end()
					->end()
					->integerNode('min_range')->min(1)->end()
					->floatNode('splash_ratio')->min(0)->max(1)->end()
					->booleanNode('split_fire')->end()
					->arrayNode('preferred_targets')
						->enumPrototype()->values(array_map(fn (ShipClass $class) => $class->value, ShipClass::cases()))->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}
}
