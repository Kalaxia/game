<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\DependencyInjection;

use App\Modules\Ares\Domain\Model\ShipCategory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ShipCategoryConfiguration implements ConfigurationInterface
{
	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder('ship_categories');

		$treeBuilder->getRootNode()
			->isRequired()
			->requiresAtLeastOneElement()
			->useAttributeAsKey('id')
			->arrayPrototype()
				->children()
					->integerNode('id')->min(1)->max(count(ShipCategory::cases()))->end()
					->integerNode('class')->isRequired()->end()
					->integerNode('pev')->isRequired()->end()
					->integerNode('life')->isRequired()->end()
					->integerNode('speed')->isRequired()->end()
					->integerNode('defense')->isRequired()->end()
					->arrayNode('attack')
						->isRequired()
						->integerPrototype()->min(0)->end()
					->end()
					->integerNode('cost')->isRequired()->end()
					->integerNode('time')->isRequired()->end()
					->integerNode('resourcePrice')->isRequired()->end()
					->integerNode('points')->isRequired()->end()
					->scalarNode('picto')->isRequired()->end()
					->scalarNode('thumbnail')->isRequired()->end()
					->integerNode('techno')->isRequired()->end()
				->end()
			->end();

		return $treeBuilder;
	}
}
