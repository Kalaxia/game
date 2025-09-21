<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class IndustrySchemasExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container): void
	{
		$configuration = $this->getConfiguration($configs, $container);

		$config = $this->processConfiguration($configuration, $configs);

		$container->setParameter('app.industry_schemas', $config);
	}

	public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
	{
		return new IndustrySchemasConfiguration();
	}
}
