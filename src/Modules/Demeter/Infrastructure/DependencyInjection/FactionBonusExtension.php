<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class FactionBonusExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container): void
	{
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);

		$container->setParameter('app.faction_bonuses', $config);
	}

	public function getAlias(): string
	{
		return 'faction_bonuses';
	}

	public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
	{
		return new FactionBonusConfiguration();
	}
}
