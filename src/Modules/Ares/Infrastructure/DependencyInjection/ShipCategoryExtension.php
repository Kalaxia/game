<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class ShipCategoryExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container): void
	{
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);

		$container->setParameter('app.ship_categories', $config);
	}

	public function getAlias(): string
	{
		return 'ship_categories';
	}

	public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
	{
		return new ShipCategoryConfiguration();
	}
}
