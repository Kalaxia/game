<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class FactionBonusConfiguration implements ConfigurationInterface
{
	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder('faction_bonuses');

		$treeBuilder->getRootNode()
			->isRequired()
			->requiresAtLeastOneElement()
			->useAttributeAsKey('id')
			->arrayPrototype()
				->children()
					->integerNode('id')->min(0)->end()
					->scalarNode('path')->defaultNull()->end()
					->scalarNode('title')->end()
					->scalarNode('desc')->end()
				->end()
			->end();

		return $treeBuilder;
	}
}
