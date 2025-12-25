<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\DependencyInjection;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class IndustrySchemasConfiguration implements ConfigurationInterface
{
	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder('industry_schemas');

		$treeBuilder->getRootNode()
			->children()
				->arrayNode('ships')
					->arrayPrototype()
						->children()
							->enumNode('slug')->enumFqcn(ShipProductSlug::class)->end()
							->enumNode('ship_category')->enumFqcn(ShipCategory::class)->end()
							->integerNode('quantity')->min(1)->end()
							->arrayNode('requirements')
								->arrayPrototype()
									->children()
										->enumNode('slug')->enumFqcn(ComponentProductSlug::class)->end()
										->integerNode('quantity')->min(1)->end()
									->end()
								->end()
							->end()
						->end()
					->end()
				->end()
				->arrayNode('components')
					->arrayPrototype()
						->children()
							->enumNode('slug')->enumFqcn(ComponentProductSlug::class)->end()
							->enumNode('type')->enumFqcn(ComponentType::class)->end()
							->integerNode('quantity')->min(1)->end()
							->arrayNode('requirements')
								->arrayPrototype()
									->children()
										->enumNode('type')->enumFqcn(ResourceType::class)->end()
										->integerNode('quantity')->min(1)->end()
									->end()
								->end()
							->end()
						->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}
}
