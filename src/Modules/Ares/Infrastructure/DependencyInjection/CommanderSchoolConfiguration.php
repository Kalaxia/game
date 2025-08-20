<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class CommanderSchoolConfiguration implements ConfigurationInterface
{
	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder('commander_school');

		$treeBuilder->getRootNode()
			->isRequired()
			->validate()
				->ifTrue(static fn ($values) => $values['maximum_experience'] <= $values['minimum_experience'])
				->thenInvalid('Maximum experience must be greater than minimum experience.')
			->end()
			->children()
				->integerNode('credits_cost')->min(0)->isRequired()->end()
				->integerNode('minimum_experience')->min(0)->isRequired()->end()
				->integerNode('maximum_experience')->min(0)->isRequired()->end()
				->integerNode('points')->min(0)->isRequired()->end()
			->end();

		return $treeBuilder;
	}
}
