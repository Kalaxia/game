<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DependencyInjection;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class FactionConfiguration implements ConfigurationInterface
{
	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder('factions');

		$treeBuilder->getRootNode()
			->isRequired()
			->requiresAtLeastOneElement()
			->useAttributeAsKey('id')
			->arrayPrototype()
				->children()
					->integerNode('id')->min(1)->max(20)->end()
					->scalarNode('officialName')->end()
					->scalarNode('popularName')->end()
					->scalarNode('government')->end()
					->scalarNode('demonym')->end()
					->scalarNode('devise')->end()
					->enumNode('regime')->values([
						Color::REGIME_DEMOCRATIC,
						Color::REGIME_ROYALISTIC,
						Color::REGIME_THEOCRATIC
					])->end()
					->scalarNode('factionPoint')->end()
					->arrayNode('status')
						->scalarPrototype()->end()
						->validate()
							->ifTrue(fn ($statuses) => count($statuses) !== 6)
							->thenInvalid('Faction status must have 6 statuses')
						->end()
					->end()
					->scalarNode('desc1')->end()
					->scalarNode('desc2')->end()
					->scalarNode('desc3')->end()
					->scalarNode('desc4')->end()
					->arrayNode('bonus')
						->enumPrototype()->values([
							ColorResource::DEFENSELITTLESHIPBONUS,
							ColorResource::PRICEBIGSHIPBONUS,
							ColorResource::SPEEDLITTLESHIPBONUS,
							ColorResource::DEFENSELITTLESHIPMALUS,
							ColorResource::COMMERCIALROUTEINCOMEBONUS,
							ColorResource::COMMERCIALROUTEPRICEBONUS,
							ColorResource::TAXBONUS,
							ColorResource::COLOPRICEBONUS,
							ColorResource::LOOTRESOURCESMALUS,
							ColorResource::RAFINERYBONUS,
							ColorResource::STORAGEBONUS,
							ColorResource::BIGACADEMICBONUS,
							ColorResource::TECHNOLOGYBONUS,
							ColorResource::COMMANDERSCHOOLBONUS,
							ColorResource::LITTLEACADEMICBONUS,
						])->end()
					->end()
					->integerNode('mandateDuration')->end()
					->scalarNode('campaignDesc')->end()
					->scalarNode('senateDesc')->end()
				->end()
			->end();

		return $treeBuilder;
	}
}
