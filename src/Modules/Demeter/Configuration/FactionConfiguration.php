<?php

namespace App\Modules\Demeter\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class FactionConfiguration implements ConfigurationInterface
{

	public function getConfigTreeBuilder()
	{

		$treeBuilder = new TreeBuilder('kalaxia_factions');

		$treeBuilder->getRootNode()
			->isRequired()
			->children()
				->arrayNode('factions')
					->useAttributeAsKey('name')
					->arrayPrototype()
						->children()
							->integerNode('id')->end()
							->scalarNode('officielName')->end()
							->scalarNode('popularName')->end()
							->scalarNode('government')->end()
							->scalarNode('demonym')->end()
							->scalarNode('factionPoint')->end()
						->end()
					->end()
				->end()
			->end()
		;


		return $treeBuilder;
	}
}
