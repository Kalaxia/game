<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class BattleShipTypeExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container): void
	{
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);

		$container->setParameter('app.battle_ship_types', $config);
	}

	public function getAlias(): string
	{
		return 'battle_ship_types';
	}

	public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
	{
		return new BattleShipTypeConfiguration();
	}
}
